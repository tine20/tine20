<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
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
 
abstract class ActiveSync_Controller_Abstract
{
    /**
     * timestamp to use for all sync requests
     *
     * @var Zend_Date
     */
    protected $_syncTimeStamp;
    
    /**
     * class to use for search entries
     *
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_contentFilterClass;
    
    /**
     * instance of the content specific controller
     *
     * @var unknown_type
     */
    protected $_contentController;
    
    /**
     * name of Tine 2.0 backend application
     * 
     * gets set by the instance of this abstract class
     *
     * @var string
     */
    protected $_applicationName;
    
    /**
     * name of Tine 2.0 model to use
     * 
     * strip of the applicationnamel and "model"
     * for example "Addressbook_Model_Contacts" becomes "Contacts"
     *
     * @var string
     */
    protected $_modelName;
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType;
    
    /**
     * name of special folder
     * 
     * get used when the client does not support more that one folder
     *
     * @var string
     */
    protected $_specialFolderName;
    
    /**
     * the constructor
     *
     * @param Zend_Date $_syncTimeStamp
     */
    public function __construct(Zend_Date $_syncTimeStamp)
    {
        if(empty($this->_applicationName)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_applicationName can not be empty');
        }
        
        if(empty($this->_modelName)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_modelName can not be empty');
        }
        
        if(empty($this->_defaultFolderType)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_defaultFolderType can not be empty');
        }
        
        if(empty($this->_folderType)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_folderType can not be empty');
        }
        
        if(empty($this->_specialFolderName)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_specialFolderName can not be empty');
        }
        
        $this->_syncTimeStamp = $_syncTimeStamp;
        $this->_contentFilterClass = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
        $this->_contentController = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
        
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getFolders()
    {
        $folders = array();
        
        $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Container::GRANT_READ);
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => (count($folders) == 0) ? $this->_defaultFolderType : $this->_folderType
            );
        }
        
        $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Container::GRANT_READ);
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => $this->_folderType
            );
        }
        
        // we ignore the folders of others users for now
        
        return $folders;
    }
    
    /**
     * get folder identified by $_folderId
     *
     * @param string $_folderId
     * @return string
     */
    public function getFolder($_folderId)
    {
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($_folderId);
        } catch (Tinebase_Exception_NotFound $e) {
            throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
        } catch (Tinebase_Exception_InvalidArgument $e) {
            throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
        }
        
        $folder[$container->id] = array(
            'folderId'      => $container->id,
            'parentId'      => 0,
            'displayName'   => $container->name,
            'type'          => $this->_folderType
        );

        return $folder;
    }
    
    /**
     * get estimate of add or changed entries
     *
     * @param Zend_Date $_startTimeStamp
     * @param Zend_Date $_endTimeStamp
     * @return int total count of changed items
     */
/*    public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL)
    {
        $count = 0;
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        
        if($_startTimeStamp === NULL && $_endTimeStamp === NULL) {
            $filter = new $this->_contentFilterClass(array()); 
            $count = $this->_contentController->searchCount($filter);
        } elseif($_endTimeStamp === NULL) {
            foreach(array('creation_time', 'last_modified_time') as $fieldName) {
                $filter = new $this->_contentFilterClass(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                )); 
                $count += $this->_contentController->searchCount($filter);
            }
        } else {
            $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
            
            foreach(array('creation_time', 'last_modified_time') as $fieldName) {
                $filter = new $this->_contentFilterClass(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'before',
                        'value'     => $endTimeStamp
                    ),
                )); 
                $count += $this->_contentController->searchCount($filter);
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Count: $count Timestamps: ($startTimeStamp / $endTimeStamp)");
                    
        return $count;
    } */
    
    /**
     * add entry from xml data
     *
     * @param unknown_type $_collectionId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function add($_folderId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add entry");
        
        $entry = $this->_toTineModel($_data);
        $entry->creation_time = $this->_syncTimeStamp;
        
        $entry = $this->_contentController->create($entry);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added entry id " . $entry->getId());

        return $entry;
    }
        
    /**
     * update existing entry
     *
     * @param unknown_type $_collectionId
     * @param string $_id
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function change($_folderId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId Id: $_id");
        
        $oldEntry = $this->_contentController->get($_id); 
        
        $entry = $this->_toTineModel($_data, $oldEntry);
        $entry->last_modified_time = $this->_syncTimeStamp;
        
        $entry = $this->_contentController->update($entry);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated entry id " . $entry->getId());

        return $entry;
    }
        
    /**
     * delete entry
     *
     * @param string $_collectionId
     * @param string $_id
     */
    public function delete($_folderId, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_folderId Id: $_id");
        
        $this->_contentController->delete($_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted entry id " . $_id);
    }
    
    /**
     * search for existing entry
     *
     * @param unknown_type $_collectionId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function search($_folderId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId");
        
        $filter = $this->_toTineFilter($_data);
        
        $foundEmtries = $this->_contentController->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEmtries));
            
        return $foundEmtries;
    }
    
    /**
     * get all entries changed between to dates
     *
     * @param unknown_type $_field
     * @param unknown_type $_startTimeStamp
     * @param unknown_type $_endTimeStamp
     * @return array
     */
    public function getChanged($_folderId, $_startTimeStamp, $_endTimeStamp = NULL)
    {
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filterArray  = $this->_getFolderFilter($_folderId);
        $filterArray[] = array(
            'field'     => 'last_modified_time',
            'operator'  => 'after',
            'value'     => $startTimeStamp
        );
        
        if($endTimeStamp !== NULL) {
            $filterArray[] = array(
                'field'     => 'last_modified_time',
                'operator'  => 'before',
                'value'     => $endTimeStamp
            );
        }

        $filter = new $this->_contentFilterClass($filterArray);

        $result = $this->_contentController->search($filter, NULL, false, true);
        
        return $result;
    }    
    
    /**
     * get id's of all contacts available on the server
     *
     * @return array
     */
    public function getServerEntries($_folderId)
    {
        $folderFilter  = $this->_getFolderFilter($_folderId);
        
        $contentFilter = new $this->_contentFilterClass($folderFilter);
        
        $foundEntries  = $this->_contentController->search($contentFilter, NULL, false, true);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEntries) . ' entries');
            
        return $foundEntries;
    }
    
    private function _getFolderFilter($_folderId)
    {
        if($_folderId == $this->_specialFolderName) {
            $folderFilter = array(
                array(
                    'field'     => 'container_id',
                    'operator'  => 'specialNode',
                    'value'     => 'all'
                )
            );        
        } else {
            $folderFilter = array(
                array(
                    'field'     => 'container_id',
                    'operator'  => 'equals',
                    'value'     => $_folderId
                )
            );        
        }
        
        return $folderFilter;
    }
    
    abstract protected function _toTineModel(SimpleXMLElement $_data, $_entry = null);
    
    abstract protected function _toTineFilter(SimpleXMLElement $_data);
    
    abstract public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_folderId, $_serverId);    
}