<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync FolderDelete command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_FolderDelete extends Syncope_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderDelete';
    
    protected $_classes             = array(
        Syncope_Data_Factory::CLASS_CALENDAR,
        Syncope_Data_Factory::CLASS_CONTACTS,
        Syncope_Data_Factory::CLASS_EMAIL,
        Syncope_Data_Factory::CLASS_TASKS
    );
    
    protected $_serverId;
    
    /**
     * @var Syncope_Model_ISyncState
     */
    protected $_syncState;
    
    /**
     * parse FolderDelete request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $syncKey  = (int)$xml->SyncKey;
        $folderId = (string)$xml->ServerId;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $syncKey");        
        
        $this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $syncKey);
        
        try {
            $this->_folder = $this->_folderBackend->getFolder($this->_device, $folderId);
            
            $dataController = Syncope_Data_Factory::factory($this->_folder->class, $this->_device, $this->_syncTimeStamp);
            
            $dataController->deleteFolder($this->_folder);
            $this->_folderBackend->delete($this->_folder);
            
        } catch (Syncope_Exception_NotFound $senf) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " " . $senf->getMessage());
        }
    }
    
    /**
     * generate FolderDelete response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse()
    {
        $folderDelete = $this->_outputDom->documentElement;
        
        if($this->_syncState == false) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            if ($this->_folder instanceof Syncope_Model_IFolder) {
                $this->_syncState->counter++;
                
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_SUCCESS));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
                
                $this->_syncStateBackend->update($this->_syncState);
            } else {
                // create xml output
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncope_Command_FolderSync::STATUS_FOLDER_NOT_FOUND));
                $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $this->_syncState->counter));
            }
        }
        
        return $this->_outputDom;
    }
    
    /**
     * save folderstate (aka: remember that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_folderId the Tine 2.0 id of the folder
     */
    protected function _addFolderState($_class, $_folderId)
    {
        $folderState = new Syncope_Model_FolderState(array(
            'device_id'     => $this->_device->getId(),
            'class'         => $_class,
            'folderid'      => $_folderId,
            'creation_time' => $this->_syncTimeStamp
        ));
        
        /**
         * if the entry got added earlier, and there was an error, the entry gets added again
         * @todo it's better to wrap the whole process into a transation
         */
        try {
            $this->_folderStateBackend->create($folderState);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->_deleteFolderState($_class, $_folderId);
            $this->_folderStateBackend->create($folderState);
        }
    }
    
    /**
     * delete folderstate (aka: forget that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_contentId the Tine 2.0 id of the folder
     */
    protected function _deleteFolderState($_class, $_folderId)
    {
        $folderStateFilter = new Syncope_Model_FolderStateFilter(array(
            array(
                    'field'     => 'device_id',
                    'operator'  => 'equals',
                    'value'     => $this->_device->getId()
            ),
            array(
                    'field'     => 'class',
                    'operator'  => 'equals',
                    'value'     => $_class
            ),
            array(
                    'field'     => 'folderid',
                    'operator'  => 'equals',
                    'value'     => $_folderId
            )
        ));
        $state = $this->_folderStateBackend->search($folderStateFilter, NULL, true);
        
        if(count($state) > 0) {
            $this->_folderStateBackend->delete($state[0]);
        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " no folderstate found for " . print_r($folderStateFilter->toArray(), true));
        }
    }    
}
