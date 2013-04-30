<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync ItemOperations command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_ItemOperations extends Syncroton_Command_Wbxml 
{        
    const STATUS_SUCCESS        = 1;
    const STATUS_PROTOCOL_ERROR = 2;
    const STATUS_SERVER_ERROR   = 3;
    
    const STATUS_ITEM_FAILED_CONVERSION = 14;
    
    protected $_defaultNameSpace    = 'uri:ItemOperations';
    protected $_documentElement     = 'ItemOperations';
    
    /**
     * list of items to move
     * 
     * @var array
     */
    protected $_fetches = array();
    
    /**
     * list of folder to empty
     * 
     * @var array
     */
    protected $_emptyFolderContents = array();
    
    /**
     * parse MoveItems request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        if (isset($xml->Fetch)) {
            foreach ($xml->Fetch as $fetch) {
                $this->_fetches[] = $this->_handleFetch($fetch);
            }
        }
        
        if (isset($xml->EmptyFolderContents)) {
            foreach ($xml->EmptyFolderContents as $emptyFolderContents) {
                $this->_emptyFolderContents[] = $this->_handleEmptyFolderContents($emptyFolderContents);
            }
        }
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " fetches: " . print_r($this->_fetches, true));        
    }
    
    /**
     * generate ItemOperations response
     * 
     * @todo add multipart support to all types of fetches
     */
    public function getResponse()
    {
        // add aditional namespaces
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSyncBase' , 'uri:AirSyncBase');
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:AirSync'     , 'uri:AirSync');
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Search'      , 'uri:Search');
        
        $itemOperations = $this->_outputDom->documentElement;
        
        $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_SUCCESS));
        
        $response = $itemOperations->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Response'));
        
        foreach ($this->_fetches as $fetch) {
            $fetchTag = $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Fetch'));
            
            try {
                $dataController = Syncroton_Data_Factory::factory($fetch['store'], $this->_device, $this->_syncTimeStamp);
                
                if (isset($fetch['collectionId'])) {
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_SUCCESS));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $fetch['collectionId']));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'ServerId',     $fetch['serverId']));
                    
                    $properties = $this->_outputDom->createElementNS('uri:ItemOperations', 'Properties');
                    $dataController
                        ->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $fetch['collectionId'], 'options' => $fetch['options'])), $fetch['serverId'])
                        ->appendXML($properties, $this->_device);
                    $fetchTag->appendChild($properties);
                    
                } elseif (isset($fetch['longId'])) {
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_SUCCESS));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:Search', 'LongId', $fetch['longId']));
                    
                    $properties = $this->_outputDom->createElementNS('uri:ItemOperations', 'Properties');
                    $dataController
                        ->getEntry(new Syncroton_Model_SyncCollection(array('collectionId' => $fetch['longId'], 'options' => $fetch['options'])), $fetch['longId'])
                        ->appendXML($properties, $this->_device);
                    $fetchTag->appendChild($properties);
                    
                } elseif (isset($fetch['fileReference'])) {
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_SUCCESS));
                    $fetchTag->appendChild($this->_outputDom->createElementNS('uri:AirSyncBase', 'FileReference', $fetch['fileReference']));

                    $properties = $this->_outputDom->createElementNS('uri:ItemOperations', 'Properties');
                    
                    $fileReference = $dataController->getFileReference($fetch['fileReference']);
                    
                    // unset data field and move content to stream
                    if ($this->_requestParameters['acceptMultipart'] == true) {
                        $this->_headers['Content-Type'] = 'application/vnd.ms-sync.multipart';
                        
                        $partStream = fopen("php://temp", 'r+');
                        
                        if (is_resource($fileReference->data)) {
                            stream_copy_to_stream($fileReference->data, $partStream);
                        } else {
                            fwrite($partStream, $fileReference->data);
                        }
                        
                        unset($fileReference->data);
                        
                        $this->_parts[] = $partStream;
                        
                        $fileReference->part = count($this->_parts);
                    }
                    
                    $fileReference->appendXML($properties, $this->_device);
                    
                    $fetchTag->appendChild($properties);
                }
            } catch (Syncroton_Exception_NotFound $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_ITEM_FAILED_CONVERSION));
            } catch (Exception $e) {
                //echo __LINE__; echo $e->getMessage(); echo $e->getTraceAsString();
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', Syncroton_Command_ItemOperations::STATUS_SERVER_ERROR));
            }
        }
        
        foreach ($this->_emptyFolderContents as $emptyFolderContents) {
            
            try {
                $folder = $this->_folderBackend->getFolder($this->_device, $emptyFolderContents['collectionId']);

                $dataController = Syncroton_Data_Factory::factory($folder->class, $this->_device, $this->_syncTimeStamp);
                $dataController->emptyFolderContents($emptyFolderContents['collectionId'], $emptyFolderContents['options']);

                $status = Syncroton_Command_ItemOperations::STATUS_SUCCESS;
            }
            catch (Syncroton_Exception_Status_ItemOperations $e) {
                $status = $e->getCode();
            }
            catch (Exception $e) {
                $status = Syncroton_Exception_Status_ItemOperations::ITEM_SERVER_ERROR;
            }

            $emptyFolderContentsTag = $this->_outputDom->createElementNS('uri:ItemOperations', 'EmptyFolderContents');
            
            $emptyFolderContentsTag->appendChild($this->_outputDom->createElementNS('uri:ItemOperations', 'Status', $status));
            $emptyFolderContentsTag->appendChild($this->_outputDom->createElementNS('uri:AirSync', 'CollectionId', $emptyFolderContents['collectionId']));
            
            $response->appendChild($emptyFolderContentsTag);
        }
        
        return $this->_outputDom;
    }
    
    protected function _handleFetch(SimpleXMLElement $fetch)
    {
        $fetchArray = array(
            'store' => (string)$fetch->Store,
            'options' => array()
        );
        
        // try to fetch element from namespace AirSync
        $airSync = $fetch->children('uri:AirSync');
        
        if (isset($airSync->CollectionId)) {
            $fetchArray['collectionId'] = (string)$airSync->CollectionId;
            $fetchArray['serverId']     = (string)$airSync->ServerId;
        }
        
        // try to fetch element from namespace Search
        $search = $fetch->children('uri:Search');
        
        if (isset($search->LongId)) {
            $fetchArray['longId'] = (string)$search->LongId;
        }
        
        // try to fetch element from namespace AirSyncBase
        $airSyncBase = $fetch->children('uri:AirSyncBase');
        
        if (isset($airSyncBase->FileReference)) {
            $fetchArray['fileReference'] = (string)$airSyncBase->FileReference;
        }
        
        if (isset($fetch->Options)) {
            // try to fetch element from namespace AirSyncBase
            $airSyncBase = $fetch->Options->children('uri:AirSyncBase');
            
            if (isset($airSyncBase->BodyPreference)) {
                foreach ($airSyncBase->BodyPreference as $bodyPreference) {
                    $type = (int) $bodyPreference->Type;
                    $fetchArray['options']['bodyPreferences'][$type] = array(
                        'type' => $type
                    );
            
                    // optional
                    if (isset($bodyPreference->TruncationSize)) {
                        $fetchArray['options']['bodyPreferences'][$type]['truncationSize'] = (int) $bodyPreference->TruncationSize;
                    }
                    
                    // optional
                    if (isset($bodyPreference->AllOrNone)) {
                        $fetchArray['options']['bodyPreferences'][$type]['allOrNone'] = (int) $bodyPreference->AllOrNone;
                    }
                }
            }
        }
        
        return $fetchArray;
    }
    
    protected function _handleEmptyFolderContents(SimpleXMLElement $emptyFolderContent)
    {
        $folderArray = array(
            'collectiondId' => null,
            'options'       => array('deleteSubFolders' => FALSE)
        );
        
        // try to fetch element from namespace AirSync
        $airSync = $emptyFolderContent->children('uri:AirSync');
        
        $folderArray['collectionId'] = (string)$airSync->CollectionId;
        
        if (isset($emptyFolderContent->Options)) {
            $folderArray['options']['deleteSubFolders'] = isset($emptyFolderContent->Options->DeleteSubFolders); 
        }
        
        return $folderArray;
    }
}
