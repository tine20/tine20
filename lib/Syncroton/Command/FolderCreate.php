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
 * class to handle ActiveSync FolderSync command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_FolderCreate extends Syncroton_Command_Wbxml 
{        
    protected $_defaultNameSpace    = 'uri:FolderHierarchy';
    protected $_documentElement     = 'FolderCreate';
    
    protected $_classes             = array(
        Syncroton_Data_Factory::CLASS_CALENDAR,
        Syncroton_Data_Factory::CLASS_CONTACTS,
        Syncroton_Data_Factory::CLASS_EMAIL,
        Syncroton_Data_Factory::CLASS_TASKS
    );
    
    /**
     * synckey sent from client
     *
     * @var string
     */
    protected $_syncKey;
    protected $_parentId;
    protected $_displayName;
    protected $_type;
    
    /**
     * parse FolderCreate request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        $this->_syncKey     = (int)$xml->SyncKey;
        $this->_parentId    = (string)$xml->ParentId;
        $this->_displayName = (string)$xml->DisplayName;
        $this->_type        = (int)$xml->Type;
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " synckey is $this->_syncKey");        
        
        switch((int)$xml->Type) {
            case Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED:
                $this->_class = Syncroton_Data_Factory::CLASS_CALENDAR;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED:
                $this->_class = Syncroton_Data_Factory::CLASS_CONTACTS;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED:
                $this->_class = Syncroton_Data_Factory::CLASS_EMAIL;
                break;
                
            case Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED:
                $this->_class = Syncroton_Data_Factory::CLASS_TASKS;
                break;
                
            default:
                throw new Syncroton_Exception_UnexpectedValue('invalid type defined');
                break;
        }
        
        $this->_syncState = $this->_syncStateBackend->validate($this->_device, 'FolderSync', $this->_syncKey);
    }
    
    /**
     * generate FolderCreate response
     */
    public function getResponse()
    {
        $folderCreate = $this->_outputDom->documentElement;
        
        if($this->_syncState == false) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->info(__METHOD__ . '::' . __LINE__ . " INVALID synckey");
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status', Syncroton_Command_FolderSync::STATUS_INVALID_SYNC_KEY));
        } else {
            $this->_syncState->counter++;
            
            $dataController = Syncroton_Data_Factory::factory($this->_class, $this->_device, $this->_syncTimeStamp);
            
            $folder = $dataController->createFolder(new Syncroton_Model_Folder(array(
                'device_id'         => $this->_device,
                'class'             => $this->_class,
                'parentid'          => $this->_parentId,
                'displayname'       => $this->_displayName,
                'type'              => $this->_type,
                'creation_time'     => $this->_syncTimeStamp,
                'lastfiltertype'    => null
            )));
            
            // create xml output
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'Status',   Syncroton_Command_FolderSync::STATUS_SUCCESS));
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'SyncKey',  $this->_syncState->counter));
            $folderCreate->appendChild($this->_outputDom->createElementNS('uri:FolderHierarchy', 'ServerId', $folder->folderid));

            // store folder in state backend
            $this->_folderBackend->create($folder);            
            
            $this->_syncStateBackend->update($this->_syncState);
        }
        
        return $this->_outputDom;
    }
}
