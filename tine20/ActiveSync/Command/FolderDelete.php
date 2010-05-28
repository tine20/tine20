<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: FolderDelete.php 6991 2009-02-25 12:35:19Z p.schuele@metaways.de $
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 */
class ActiveSync_Command_FolderDelete extends ActiveSync_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderDelete';
    
    protected $_classes             = array('Contacts', 'Tasks', 'Email');
    
    /**
     * synckey sent from client
     *
     * @var string
     */
    protected $_syncKey;
    protected $_serverId;
    
    /**
     * the folderState sql backend
     *
     * @var ActiveSync_Backend_FolderState
     */
    protected $_folderStateBackend;
    
    /**
     * instance of ActiveSync_Controller
     *
     * @var ActiveSync_Controller
     */
    protected $_controller;
    
    /**
     * the constructor
     *
     * @param ActiveSync_Model_Device $_device
     */
    public function __construct(ActiveSync_Model_Device $_device)
    {
        parent::__construct($_device);
        
        $this->_folderStateBackend   = new ActiveSync_Backend_FolderState();
        $this->_controller           = ActiveSync_Controller::getInstance();

    }
    
    /**
     * parse FolderDelete request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $this->_syncKey     = (int)$xml->SyncKey;
        $this->_serverId    = (string)$xml->ServerId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey");        
    }
    
    /**
     * generate FolderDelete response
     *
     * @todo currently we support only the main folder which contains all contacts/tasks/events/notes per class
     */
    public function getResponse()
    {
        $folderDelete = $this->_outputDom->documentElement;
        
        if($this->_syncKey > '0' && $this->_controller->validateSyncKey($this->_device, $this->_syncKey, 'FolderSync') !== true) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', ActiveSync_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            $newSyncKey = $this->_syncKey + 1;
            
            // create xml output
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', ActiveSync_Command_FolderSync::STATUS_SUCCESS));
            $folderDelete->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey', $newSyncKey));

            $this->_deleteFolderState('Email', $this->_serverId);
            
            $this->_controller->updateSyncKey($this->_device, $newSyncKey, $this->_syncTimeStamp, 'FolderSync');
        }
        
        parent::getResponse();
    }
    
    /**
     * save folderstate (aka: remember that we have sent the folder to the client)
     *
     * @param string $_class the class from the xml
     * @param string $_folderId the Tine 2.0 id of the folder
     */
    protected function _addFolderState($_class, $_folderId)
    {
        $folderState = new ActiveSync_Model_FolderState(array(
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
        $folderStateFilter = new ActiveSync_Model_FolderStateFilter(array(
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