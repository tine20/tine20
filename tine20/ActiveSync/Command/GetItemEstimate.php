<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_GetItemEstimate extends ActiveSync_Command_Wbxml 
{
    const STATUS_SUCCESS                = 1;
    /**
     * A collection was invalid or one of the specified collection IDs was invalid.
     *
     */
    const STATUS_INVALID_COLLECTION     = 2;
    /**
     * Sync state has not been primed yet. The Sync command must be performed first.
     *
     */
    const STATUS_SYNC_STATE_NOT_PRIMED  = 3;
    const STATUS_INVALID_SYNC_KEY       = 4;
    
    protected $_defaultNameSpace = 'uri:ItemEstimate';
    protected $_documentElement = 'GetItemEstimate';
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @return resource
     */
    public function handle()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $xml = new SimpleXMLElement($this->_inputDom->saveXML(), LIBXML_NOWARNING);
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            $class          = (string)$xmlCollection->Class;
            $collectionId   = (string)$xmlCollection->CollectionId;
            
            // fetch values from a different namespace
            $airSyncValues  = $xmlCollection->children('uri:AirSync');
            $clientSyncKey  = (string)$airSyncValues->SyncKey;
            $filterType     = (string)$airSyncValues->FilterType;
            
            $collectionData = array(
                'syncKey'       => $clientSyncKey,
                'syncKeyValid'  => true,
                'class'         => $class,
                'collectionId'  => $collectionId,
                'filterType'    => $filterType
            );
            $this->_collections[$class] = $collectionData;
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " synckey is $clientSyncKey class: $class collectionid: $collectionId filtertype: $filterType");
            
            if($clientSyncKey === 0 || $controller->validateSyncKey($this->_device, $clientSyncKey, $class . '-' . $collectionId) !== true) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey $clientSyncKey provided");
                $this->_collections[$class]['syncKeyValid'] = false;
            }
        }
    }    
    
    public function getResponse()
    {
        $controller = ActiveSync_Controller::getInstance();
        
        $itemEstimate = $this->_outputDom->documentElement;
        
        foreach($this->_collections as $class => $collectionData) {
            $response = $itemEstimate->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Response'));
            
            if($collectionData['syncKeyValid'] !== true) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));                                              
            } else {
                $dataController = ActiveSync_Controller::dataFactory($collectionData['class'], $this->_syncTimeStamp);
                
                try {
                    // does the folder exist?
                    $dataController->getFolder($collectionData['collectionId']);
                    
                    $syncState = $controller->getSyncState($this->_device, $collectionData['class'] . '-' . $collectionData['collectionId'], $collectionData['syncKey']);
                
                    if($controller->validateSyncKey($this->_device, $collectionData['syncKey'], $collectionData['class'] . '-' . $collectionData['collectionId'])) {
                        $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                        $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                        if($collectionData['syncKey'] == 1) {
                            // this is the first sync. in most cases there are data on the server.
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', $dataController->getItemEstimate()));
                        } else {
                            // this returns only changed or modified entries
                            $modifiedEntries = $dataController->getItemEstimate($syncState->lastsync, $this->_syncTimeStamp);
                            
                            // get the count of deleted entries
                            $contentStateBackend  = new ActiveSync_Backend_ContentState();
                            $allClientEntries = $contentStateBackend->getClientState($this->_device, $collectionData['class']);
                            $allServerEntries = $dataController->getServerEntries();
                            
                            // add difference of entries available on the server and entries sent to the server
                            $modifiedEntries += abs(count($allClientEntries) - count($allServerEntries));
                            
                            $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', $modifiedEntries));
                        }
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                        $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                        $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                        $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));              
                    }
                } catch (ActiveSync_Exception_FolderNotFound $e) {
                    error_log(__METHOD__ . '::' . __LINE__ . " folder not found");
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_COLLECTION));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Class', $collectionData['class']));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));                
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));              
                }
                
            }
        }
                
        parent::getResponse();
    }
}