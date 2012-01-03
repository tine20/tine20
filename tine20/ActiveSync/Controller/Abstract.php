<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
 
abstract class ActiveSync_Controller_Abstract implements ActiveSync_Controller_Interface
{
    /**
     * information about the current device
     *
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var Tinebase_DateTime
     */
    protected $_syncTimeStamp;
    
    /**
     * class to use for search entries
     *
     * @var string
     */
    protected $_contentFilterClass;
    
    /**
     * instance of the content specific controller
     *
     * @var Tinebase_Controller_Record_Abstract
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
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder;
    
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
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty;
    
    /**
     * field to sort search results by
     * 
     * @var string
     */
    protected $_sortField;
    
    /**
     * name of the contentcontoller class
     * Defaults to $this->_applicationName . '_Controller_' . $this->_modelName
     * 
     * @var string
     */
    protected $_contentControllerName;
    
    /**
     * the constructor
     *
     * @param Tinebase_DateTime $_syncTimeStamp
     */
    public function __construct(ActiveSync_Model_Device $_device, DateTime $_syncTimeStamp)
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
            $this->_specialFolderName = strtolower($this->_applicationName) . '-root';
        }
        
        $this->_device              = $_device;
        $this->_syncTimeStamp       = $_syncTimeStamp;
        
        $this->_contentFilterClass  = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
        if (empty($this->_contentControllerName)) {
            $this->_contentControllerName = $this->_applicationName . '_Controller_' . $this->_modelName;
        }
        $this->_contentController   = call_user_func(array($this->_contentControllerName, 'getInstance')); 
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getSupportedFolders()
    {
        // device supports multiple folders ?
        if(in_array(strtolower($this->_device->devicetype), array('iphone', 'ipad', 'thundertine'))) {
        
            // get the folders the user has access to
            $allowedFolders = $this->_getSyncableFolders();
            
            $wantedFolders = null;
            // maybe the user has defined a filter to limit the search results
            try {
                if(!empty($this->_device->contactsfilter_id)) {
                    $persistentFilter = Tinebase_PersistentFilter::getFilterById($this->_device->contactsfilter_id);
                    
                    foreach($persistentFilter as $filter) {
                        if($filter instanceof Tinebase_Model_Filter_Container) {
                            $wantedFolders = array_flip($filter->getContainerIds());
                        }
                    }
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
               // filter got deleted already
            }
            $folders = $wantedFolders === null ? $allowedFolders : array_intersect_key($allowedFolders, $wantedFolders);
        } else {
            
            $folders[$this->_specialFolderName] = array(
                'folderId'      => $this->_specialFolderName,
                'parentId'      => 0,
                'displayName'   => $this->_applicationName,
                'type'          => $this->_defaultFolderType
            );
            
        }
        
        return $folders;
    }
    
    /**
     * Returns a set of records identified by their id's
     * 
     * @param   array $_ids       array of record identifiers
     * @return  Tinebase_Record_RecordSet 
     */
    public function getMultiple($_ids)
    {
        $records = $this->_contentController->getMultiple($_ids);
        
        $firstRecord = $records->getFirstRecord();
        if ($firstRecord) {
            // get tags / alarms
            if ($firstRecord->has('tags')) {
                Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($records);
            }
            if ($firstRecord->has('alarms')) {
                $this->_contentController->getAlarms($records);
            }
        }
        
        return $records;
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/Controller/ActiveSync_Controller_Interface#moveItem()
     */
    public function moveItem($_srcFolder, $_srcItem, $_dstFolder)
    {
        $item = $this->_contentController->get($_srcItem);
        
        $item->container_id = $_dstFolder;
        
        $item = $this->_contentController->update($item);
        
        return $item->getId();
    }
    
    /**
     * get folder identified by $_folderId
     *
     * @param string $_folderId
     * @return string
     */
    public function getFolder($_folderId)
    {
        $folder = array();
        
        if($_folderId == $this->_specialFolderName) {
            $folder[$this->_specialFolderName] = array(
                'folderId'      => $this->_specialFolderName,
                'parentId'      => 0,
                'displayName'   => $this->_applicationName,
                'type'          => $this->_defaultFolderType
            );
            
        } else {
            try {
                $container = Tinebase_Container::getInstance()->getContainerById($_folderId);
            } catch (Tinebase_Exception_NotFound $e) {
                throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
            } catch (Tinebase_Exception_InvalidArgument $e) {
                throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
            }
            
            if(!Tinebase_Core::getUser()->hasGrant($_folderId, Tinebase_Model_Grants::GRANT_SYNC)) {
            	throw new ActiveSync_Exception_FolderNotFound('No sync right for folder: ' . $_folderId);
            }
            
            $folder[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => $this->_folderType
            );
        }

        return $folder;
    }
    
    /**
     * add entry from xml data
     *
     * @param string $_folderId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function add($_folderId, SimpleXMLElement $_data)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add entry");
        
        $entry = $this->toTineModel($_data);
        $entry->creation_time = $this->_syncTimeStamp;
        $entry->created_by = Tinebase_Core::getUser()->getId();
        
        // container_id gets set to personal folder in application specific controller if missing
        if($_folderId != $this->_specialFolderName) {
            $entry->container_id = $_folderId;
        } else {
            $containerId = Tinebase_Core::getPreference('ActiveSync')->{$this->_defaultFolder};
            
            if (Tinebase_Core::getUser()->hasGrant($containerId, Tinebase_Model_Grants::GRANT_ADD) === true) {
                $entry->container_id = $containerId;
            }
        }
            
        $entry = $this->_contentController->create($entry);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added entry id " . $entry->getId());

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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId Id: $_id");
        
        $oldEntry = $this->_contentController->get($_id); 
        
        $entry = $this->toTineModel($_data, $oldEntry);
        $entry->last_modified_time = $this->_syncTimeStamp;
        
        $entry = $this->_contentController->update($entry);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated entry id " . $entry->getId());

        return $entry;
    }
        
    /**
     * delete entry
     *
     * @param  string  $_collectionId
     * @param  string  $_id
     * @param  array   $_options
     */
    public function delete($_folderId, $_id, $_options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_folderId Id: $_id");
        
        $this->_contentController->delete($_id);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted entry id " . $_id);
    }
    
    /**
     * search for existing entry in all syncable folders
     *
     * @param string            $_forlderId
     * @param SimpleXMLElement  $_data
     * @return Tinebase_Record_Abstract
     */
    public function search($_folderId, SimpleXMLElement $_data)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId");
        
        $filterArray  = $this->_toTineFilterArray($_data);
        $filter       = new $this->_contentFilterClass($filterArray);
        
        $this->_getContainerFilter($filter, $_folderId);
        
        $foundEmtries = $this->_contentController->search($filter);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEmtries));
            
        return $foundEmtries;
    }
    
    /**
     * used by the mail backend only. Used to update the folder cache
     * 
     * @param  string  $_folderId
     */
    public function updateCache($_folderId)
    {
        // does nothing by default
    }
    
    /**
     * add container filter to filter group
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_containerId
     */
    protected function _getContainerFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_containerId)
    {
        $syncableContainers = $this->_getSyncableFolders();
        
        $containerIds = array();
        
        if($_containerId == $this->_specialFolderName) {
            $containerIds = array_keys($syncableContainers);
        } elseif(array_key_exists($_containerId, $syncableContainers)) {
            $containerIds = array($_containerId);        
        }

        $_filter->addFilter($_filter->createFilter('container_id', 'in', $containerIds));
    }
    
    /**
     * get syncable folders
     * 
     * @return array
     */
    protected function _getSyncableFolders()
    {
        $folders = array();
        
        $containers = Tinebase_Container::getInstance()->getContainerByACL(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_SYNC);
        
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => (count($folders) == 0) ? $this->_defaultFolderType : $this->_folderType
            );
        }
                
        return $folders;
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
        $filter = new $this->_contentFilterClass();
        
        try {
            $persistentFilterId = $this->_device->{$this->_filterProperty} ? 
                $this->_device->{$this->_filterProperty} : 
                Tinebase_Core::getPreference($this->_applicationName)->defaultpersistentfilter;
                
            $filter = Tinebase_PersistentFilter::getFilterById($persistentFilterId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // filter got deleted already
        }
        
        $this->_getContentFilter($filter, 0);
        $this->_getContainerFilter($filter, $_folderId);

        $startTimeStamp = ($_startTimeStamp instanceof DateTime) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof DateTime) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filter->addFilter(new Tinebase_Model_Filter_DateTime(
            'last_modified_time',
            'after',
            $startTimeStamp
        ));
        
        if($endTimeStamp !== NULL) {
            $filter->addFilter(new Tinebase_Model_Filter_DateTime(
                'last_modified_time',
                'before',
                $endTimeStamp
            ));
        }
        
        $result = $this->_contentController->search($filter, NULL, false, true, 'sync');
        
        return $result;
    }    
    
    /**
     * get id's of all entries available on the server
     *
     * @param string $_folderId
     * @param int $_filterType
     * @return array
     */
    public function getServerEntries($_folderId, $_filterType)
    {
        $filter = new $this->_contentFilterClass();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter class: " . get_class($filter));
                
        // apply the default search filter only to devices which do not support multiple folders
        if($this->_specialFolderName == $_folderId) {
            $persistentFilterId = $this->_device->{$this->_filterProperty} ?
                $this->_device->{$this->_filterProperty} :
                Tinebase_Core::getPreference($this->_applicationName)->defaultpersistentfilter;
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " defaultpersistentfilter: " . Tinebase_Core::getPreference($this->_applicationName)->defaultpersistentfilter);
        } else {
            $persistentFilterId = $this->_device->{$this->_filterProperty} ?
                $this->_device->{$this->_filterProperty} :
                null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter id: " . $persistentFilterId);

        if (!empty($persistentFilterId)) {
            try {
                $persistentFilter = Tinebase_PersistentFilter::getFilterById($persistentFilterId);
                // @todo is this if statement really needed? either the filter got found or a Tinebase_Exception_NotFound got thrown
                if ($persistentFilter) {
                    $filter = $persistentFilter;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filter class: " . get_class($filter));
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                // filter got deleted already
            }
        }
        
        $this->_getContentFilter($filter, $_filterType);
        $this->_getContainerFilter($filter, $_folderId);
        
        if(!empty($this->_sortField)) {
            $pagination = new Tinebase_Model_Pagination(array(
                'sort' => $this->_sortField
            ));
        } else {
            $pagination = null;
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " assembled {$this->_contentFilterClass}: " . print_r($filter->toArray(), TRUE));
        $result = $this->_contentController->search($filter, $pagination, false, true, 'sync');
        
        return $result;
    }

    /**
     * return contentfilter array
     * 
     * @param int $_filterType
     * @return array
     */
    protected function _getContentFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_filterType)
    {
        return array();
    }
    
    /**
     * convert contact from xml to Tinebase_Record_Interface
     *
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Interface
     */
    abstract public function toTineModel(SimpleXMLElement $_data, $_entry = null);
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return array
     */
    abstract protected function _toTineFilterArray(SimpleXMLElement $_data);
    
    /**
     * append entry data to xml element
     *
     * @param DOMElement  $_xmlNode   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    abstract public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId, array $_options, $_neverTruncate = false);
    
    /**
     * removed control chars from string which are not allowd in ActiveSync
     * 
     * @param  string|array $_dirty
     * @return string
     */
    public function removeControlChars($_dirty)
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', null, $_dirty);
    }
}
