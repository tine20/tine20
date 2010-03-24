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
     * information about the current device
     *
     * @var ActiveSync_Model_Device
     */
    protected $_device;
    
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
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty;
    
    /**
     * the constructor
     *
     * @param Zend_Date $_syncTimeStamp
     */
    public function __construct(ActiveSync_Model_Device $_device, Zend_Date $_syncTimeStamp)
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
        $this->_contentController   = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    abstract public function getSupportedFolders();
    
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
     * @param unknown_type $_collectionId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function add($_folderId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add entry");
        
        $entry = $this->_toTineModel($_data);
        $entry->creation_time = $this->_syncTimeStamp;
        $entry->created_by = Tinebase_Core::getUser()->getId();
        // container_id gets set to personal folder in application specific controller if missing
        if($_folderId != $this->_specialFolderName) {
            $entry->container_id = $_folderId;
        }
            
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
     * @param unknown_type $_forlderId
     * @param SimpleXMLElement $_data
     * @return Tinebase_Record_Abstract
     */
    public function search($_folderId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId");
        
        $filterArray   = $this->_toTineFilter($_data);
        $filterArray[] = $this->_getContainerFilter($_folderId);
        
        $filter = new $this->_contentFilterClass($filterArray);
        
        $foundEmtries = $this->_contentController->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEmtries));
            
        return $foundEmtries;
    }
    
    protected function _getContainerFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_containerId)
    {
        $syncableContainers = $this->_getSyncableFolders();
        
        $containerIds = array();
        
        if($_containerId == $this->_specialFolderName) {
            $containerIds = array_keys($syncableContainers);
        } elseif(array_key_exists($_containerId, $syncableContainers)) {
            $containerIds = array($_containerId);        
        }
                
        $_filter->addFilter(new Tinebase_Model_Filter_Container(
            'container_id', 
            'in', 
            $containerIds, 
            array('applicationName' => $this->_applicationName)
        ));
        
        #$filter = array(
        #    'field'     => 'container_id',
        #    'operator'  => 'in',
        #    'value'     => $containerIds
        #);
        
        #return $filter;
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
        if(!empty($this->_device->{$this->_filterProperty})) {
            $filter = Tinebase_PersistentFilter::getFilterById($this->_device->{$this->_filterProperty});
        } else {
            $filter = new $this->_contentFilterClass();
        }
        
        $this->_getContentFilter($filter, 0);
        $this->_getContainerFilter($filter, $_folderId);

        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
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
     * get id's of all contacts available on the server
     *
     * @param string $_folderId
     * @param int $_filterType
     * @return array
     */
    public function getServerEntries($_folderId, $_filterType)
    {
        if(!empty($this->_device->{$this->_filterProperty})) {
            $filter = Tinebase_PersistentFilter::getFilterById($this->_device->{$this->_filterProperty});
        } else {
            $filter = new $this->_contentFilterClass();
        }
        
        $this->_getContentFilter($filter, $_filterType);
        $this->_getContainerFilter($filter, $_folderId);
        
        $result = $this->_contentController->search($filter, NULL, false, true, 'sync');
        
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
            
    abstract protected function _toTineModel(SimpleXMLElement $_data, $_entry = null);
    
    abstract protected function _toTineFilterArray(SimpleXMLElement $_data);
    
    abstract public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId);    
}