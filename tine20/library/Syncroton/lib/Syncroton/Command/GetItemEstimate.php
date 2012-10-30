<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync GetItemEstimate command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_GetItemEstimate extends Syncroton_Command_Wbxml 
{
    const STATUS_SUCCESS                = 1;
    const STATUS_INVALID_COLLECTION     = 2;
    const STATUS_SYNC_STATE_NOT_PRIMED  = 3;
    const STATUS_INVALID_SYNC_KEY       = 4;
    
    protected $_defaultNameSpace    = 'uri:ItemEstimate';
    protected $_documentElement     = 'GetItemEstimate';
    
    /**
     * list of collections
     *
     * @var array
     */
    protected $_collections = array();
    
    /**
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);
        
        foreach ($xml->Collections->Collection as $xmlCollection) {
            
            // fetch values from a different namespace
            $airSyncValues  = $xmlCollection->children('uri:AirSync');
            
            $collectionData = array(
                'syncKey'       => (int)$airSyncValues->SyncKey,
                'collectionId'  => (string) $xmlCollection->CollectionId,
                'class'         => isset($xmlCollection->Class) ? (string) $xmlCollection->Class : null,
                'filterType'    => isset($airSyncValues->Options) && isset($airSyncValues->Options->FilterType) ? (int)$airSyncValues->Options->FilterType : 0
            );
            
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " synckey is {$collectionData['syncKey']} class: {$collectionData['class']} collectionid: {$collectionData['collectionId']} filtertype: {$collectionData['filterType']}");
            
            try {
                // does the folder exist?
                $collectionData['folder'] = $this->_folderBackend->getFolder($this->_device, $collectionData['collectionId']);
                $collectionData['folder']->lastfiltertype = $collectionData['filterType'];
                
                if($collectionData['syncKey'] === 0) {
                    $collectionData['syncState'] = new Syncroton_Model_SyncState(array(
                        'device_id' => $this->_device,
                        'counter'   => 0,
                        'type'      => $collectionData['folder'],
                        'lastsync'  => $this->_syncTimeStamp
                    ));
                    
                    // reset sync state for this folder
                    $this->_syncStateBackend->resetState($this->_device, $collectionData['folder']);
                    $this->_contentStateBackend->resetState($this->_device, $collectionData['folder']);
                    
                } else {
                    $collectionData['syncState'] = $this->_syncStateBackend->validate($this->_device, $collectionData['folder'], $collectionData['syncKey']);
                }
            } catch (Syncroton_Exception_NotFound $senf) {
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
            }
            
            $this->_collections[$collectionData['collectionId']] = $collectionData;
        }
    }    
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Command_Wbxml::getResponse()
     */
    public function getResponse()
    {
        $itemEstimate = $this->_outputDom->documentElement;
        
        foreach($this->_collections as $collectionData) {
            $response = $itemEstimate->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Response'));

            // invalid collectionid provided
            if (empty($collectionData['folder'])) {
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " folder does not exist");
                
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_COLLECTION));
                $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));
                
            } elseif (! ($collectionData['syncState'] instanceof Syncroton_Model_ISyncState)) {
                if ($this->_logger instanceof Zend_Log)
                    $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " invalid synckey ${collectionData['syncKey']} provided");
                /*
                 * Android phones (and maybe others) don't take care about status 4(INVALID_SYNC_KEY)
                 * To solve the problem we always return status 1(SUCCESS) even the sync key is invalid with Estimate set to 1.
                 * This way the phone gets forced to sync. Handling invalid synckeys during sync command works without any problems.
                 * 
                    $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_INVALID_SYNC_KEY));
                    $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                    $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 0));
                 */
                                                              
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));  
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', 1));                                              
            } else {
                $dataController = Syncroton_Data_Factory::factory($collectionData['folder']->class, $this->_device, $this->_syncTimeStamp);
                
                $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Status', self::STATUS_SUCCESS));
                $collection = $response->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Collection'));
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'CollectionId', $collectionData['collectionId']));
                if($collectionData['syncState']->counter === 0) {
                    // this is the first sync. in most cases there are data on the server.
                    $count = count($dataController->getServerEntries($collectionData['collectionId'], $collectionData['filterType']));
                } else {
                    $count = $dataController->getCountOfChanges($this->_contentStateBackend, $collectionData['folder'], $collectionData['syncState']);
                }
                $collection->appendChild($this->_outputDom->createElementNS('uri:ItemEstimate', 'Estimate', $count));
            }
            
            // folderState can be NULL in case of not existing folder
            if (isset($collectionData['folder'])) {
                $this->_folderBackend->update($collectionData['folder']);
            }
        }
                
        return $this->_outputDom;
    }
}
