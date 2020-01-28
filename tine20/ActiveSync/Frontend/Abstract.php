<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Abstract ActiveSync frontend class
 *
 * @package     ActiveSync
 * @subpackage  Frontend
 */
abstract class ActiveSync_Frontend_Abstract implements Syncroton_Data_IData
{
    const LONGID_DELIMITER = "\xe2\x87\x94"; # ⇔
    
    /**
     * information about the current device
     *
     * @var Syncroton_Model_IDevice
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
    
    protected $_defaultContainerPreferenceName;
    
    protected $_defaultContainerId;
    
    /**
     * devices that support multiple folders
     * 
     * @var array
     */
    protected $_devicesWithMultipleFolders = array(
        Syncroton_Model_Device::TYPE_IPHONE,
        'ipad',
        'thundertine',
        'windowsphone',
        'wp8',
        'windowsoutlook15',
        'playbook',
        'blackberry',
        'bb10',
        // android supports multiple folders since 4.4
        Syncroton_Model_Device::TYPE_ANDROID
    );
    
    /**
     * the constructor
     *
     * @param Tinebase_DateTime $_syncTimeStamp
     */
    public function __construct(Syncroton_Model_IDevice $_device, DateTime $_syncTimeStamp)
    {
        $denyList = ActiveSync_Config::getInstance()->get(ActiveSync_Config::DEVICE_MODEL_DENY_LIST);
        foreach ($denyList as $deny) {
            if (preg_match($deny, $_device->model)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Device model blocked: ' . $_device->model);
                throw new Tinebase_Exception_ProgramFlow(' Device model blocked: ' . $_device->model);
            }
        }

        if (empty($this->_applicationName)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_applicationName can not be empty');
        }
        
        if (empty($this->_modelName)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_modelName can not be empty');
        }
        
        if (empty($this->_defaultFolderType)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_defaultFolderType can not be empty');
        }
        
        if (empty($this->_folderType)) {
            throw new Tinebase_Exception_UnexpectedValue('$this->_folderType can not be empty');
        }
        
        if (empty($this->_specialFolderName)) {
            $this->_specialFolderName = strtolower($this->_applicationName) . '-root';
        }
        
        // this is a Syncroton_Model_Device and not a ActiveSync_Model_Device
        $this->_device              = $_device;
        $this->_device->devicetype  = strtolower($this->_device->devicetype);
        if ($this->_device->devicetype == 'ipad') {
            // map to iphone till syncroton has ipad/ios support
            $this->_device->devicetype = Syncroton_Model_Device::TYPE_IPHONE;
        }
        
        $this->_syncTimeStamp       = $_syncTimeStamp;
        
        $this->_contentFilterClass  = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
        if (empty($this->_contentControllerName)) {
            $this->_contentControllerName = $this->_applicationName . '_Controller_' . $this->_modelName;
        }
        $this->_contentController   = call_user_func(array($this->_contentControllerName, 'getInstance'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Created controller for device type ' . $this->_device->devicetype);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getAllFolders()
     */
    public function getAllFolders()
    {
        $syncrotonFolders = array();
        
        if ($this->_deviceSupportsMultipleFolders()) {
            $folders = $this->_getAllFolders();
            
            foreach ($folders as $container) {
                $syncrotonFolders[$container->id] = $this->_convertContainerToSyncrotonFolder($container);
            }
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Device does not support multiple folders for " . $this->_specialFolderName . '. Returning default container.');
            
            $syncrotonFolders[$this->_specialFolderName] = new Syncroton_Model_Folder(array(
                'serverId'      => $this->_specialFolderName,
                'parentId'      => 0,
                'displayName'   => $this->_applicationName,
                'type'          => $this->_defaultFolderType
            ));
        }
        
        return $syncrotonFolders;
    }
    
    /**
     * return default container id for application
     */
    protected function _getDefaultContainerId()
    {
        if (!$this->_defaultContainerId && $this->_defaultContainerPreferenceName) {
            $this->_defaultContainerId = Tinebase_Core::getPreference($this->_applicationName)
                ->{$this->_defaultContainerPreferenceName};
        }
        
        return $this->_defaultContainerId;
    }
    
    /**
     * get devices with multiple folders
     * 
     * @return array
     */
    protected function _getDevicesWithMultipleFolders()
    {
        return $this->_devicesWithMultipleFolders;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::moveItem()
     */
    public function moveItem($srcFolderId, $serverId, $dstFolderId)
    {
        $this->_assertContentControllerParams($srcFolderId);
        $item = $this->_contentController->get($serverId);
        
        $item->container_id = $dstFolderId;

        $item = $this->_contentController->update($item);
        
        return $item->getId();
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::createEntry()
     */
    public function createEntry($folderId, Syncroton_Model_IEntry $entry)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " create entry");

        $this->_assertContentControllerParams($folderId);
        $entry = $this->toTineModel($entry);
        
        // container_id gets set to personal folder in application specific controller if missing
        if($folderId != $this->_specialFolderName) {
            $entry->container_id = $folderId;
        } else {
            $containerId = Tinebase_Core::getPreference('ActiveSync')->{$this->_defaultFolder};
            
            if (Tinebase_Core::getUser()->hasGrant($containerId, Tinebase_Model_Grants::GRANT_ADD) === true) {
                $entry->container_id = $containerId;
            }
        }
        
        try {
            // create record (without duplicate check)
            // @see 0008486: Contacts deleted on Android device after new created contact via ActiveSync
            $this->_assertContentControllerParams($entry->container_id);
            $entry = $this->_contentController->create($entry, FALSE);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new Syncroton_Exception_AccessDenied();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " added entry id " . $entry->getId());

        return $entry->getId();
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::createFolder()
     */
    public function createFolder(Syncroton_Model_IFolder $folder)
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => $folder->displayName,
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => Tinebase_Core::getUser(),
            'backend'           => 'Sql',
            'model'             => $this->_applicationName . '_Model_' . $this->_modelName,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
        )));
        
        $folder->serverId = $container->getId();
        
        return $folder;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::deleteFolder()
     */
    public function deleteFolder($folderId)
    {
        
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::emptyFolderContents()
     */
    public function emptyFolderContents($folderId, $options)
    {
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getEntry()
     */
    public function getEntry(Syncroton_Model_SyncCollection $collection, $serverId)
    {
        // is $serverId a LongId?
        if (strpos($serverId, ActiveSync_Frontend_Abstract::LONGID_DELIMITER) !== false) {
            list($collection->collectionId, $serverId) = explode(ActiveSync_Frontend_Abstract::LONGID_DELIMITER, $serverId, 2);
        }
        
        try {
            $this->_assertContentControllerParams($collection->collectionId);
            $entry = $this->_contentController->get($serverId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Syncroton_Exception_NotFound();
        }
        
        return $this->toSyncrotonModel($entry, $collection->options);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getFileReference()
     */
    public function getFileReference($fileReference)
    {
        
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::updateEntry()
     */
    public function updateEntry($folderId, $serverId, Syncroton_Model_IEntry $entry)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " update CollectionId: $folderId Id: $serverId");
        
        try {
            $this->_assertContentControllerParams($folderId);
            $oldEntry = $this->_contentController->get($serverId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' ' . $tenf);
            throw new Syncroton_Exception_NotFound($tenf->getMessage());
        }
        
        $updatedEmtry = $this->toTineModel($entry, $oldEntry);
        // @FIXME: this skips concurrency handling
        $updatedEmtry->last_modified_time = new Tinebase_DateTime($this->_syncTimeStamp);
        
        try {
            $updatedEmtry = $this->_contentController->update($updatedEmtry);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new Syncroton_Exception_AccessDenied();
        }
        
        return $updatedEmtry->getId();
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::updateFolder()
     */
    public function updateFolder(Syncroton_Model_IFolder $folder)
    {
        if ($folder->serverId == $this->_specialFolderName) {
            throw new Syncroton_Exception_UnexpectedValue($this->_specialFolderName . " can't be updated");
        }
        
        $container = Tinebase_Container::getInstance()->get($folder->serverId);
        
        $container->name = $folder->displayName;
        
        Tinebase_Container::getInstance()->update($container);
        
        return $folder;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::deleteEntry()
     */
    public function deleteEntry($folderId, $serverId, $collectionData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " delete ColectionId: $folderId Id: $serverId");
        
        try {
            $this->_contentController->delete($serverId);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new Syncroton_Exception_AccessDenied();
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Syncroton_Exception_NotFound();
        }
    }
    
    /**
     * search for existing entry in all syncable folders
     *
     * @param string            $_forlderId
     * @param SimpleXMLElement  $_data
     * @return Tinebase_Record_Interface
     */
    #public function search($_folderId, SimpleXMLElement $_data)
    #{
    #    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_folderId");
    #    
    #    $filterArray  = $this->_toTineFilterArray($_data);
    #    $filter       = new $this->_contentFilterClass($filterArray);
    #    
    #    $this->_addContainerFilter($filter, $_folderId);
    #    
    #    $foundEmtries = $this->_contentController->search($filter);
    #
    #    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundEmtries));
    #        
    #    return $foundEmtries;
    #}
    
    /**
     * add container acl filter to filter group
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string                            $_containerId
     */
    protected function _addContainerFilter(Tinebase_Model_Filter_FilterGroup $_filter, $_containerId)
    {
        $syncableContainers = $this->_getSyncableFolders();
        
        $containerIds = array();
        
        if($_containerId == $this->_specialFolderName) {
            $containerIds = $syncableContainers->getArrayOfIds();
        } elseif(in_array($_containerId, $syncableContainers->id)) {
            $containerIds = array($_containerId);
        }

        if (!empty($containerIds)) {
            $_filter->removeFilter('container_id');
            $_filter->addFilter($_filter->createFilter('container_id', 'in', $containerIds));
        }
    }
    
    /**
     * convert Tinebase_Model_Container to Syncroton_Model_Folder
     * 
     * @param Tinebase_Model_Container $container
     * @return Syncroton_Model_Folder
     */
    protected function _convertContainerToSyncrotonFolder(Tinebase_Model_Container $container)
    {
        return new Syncroton_Model_Folder(array(
            'serverId'      => $container->id,
            'parentId'      => 0,
            'displayName'   => $container->name,
            'type'          => $container->id == $this->_getDefaultContainerId()
                                   ? $this->_defaultFolderType 
                                   : $this->_folderType
        ));
    }
    
    /**
     * get syncable folders
     * 
     * @return Tinebase_Record_RecordSet
     */
    protected function _getSyncableFolders()
    {
        $containers = Tinebase_Container::getInstance()->getContainerByACL(Tinebase_Core::getUser(), $this->_applicationName . '_Model_' . $this->_modelName, Tinebase_Model_Grants::GRANT_SYNC);
        
        return $containers;
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getChangedEntries()
     */
    public function getChangedEntries($folderId, DateTime $_startTimeStamp, DateTime $_endTimeStamp = NULL, $filterType = NULL)
    {
        $filter = $this->_getContentFilter(0);
        
        $this->_addContainerFilter($filter, $folderId);
        
        $startTimeStamp = ($_startTimeStamp instanceof DateTime) ? $_startTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof DateTime) ? $_endTimeStamp->format(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        // @todo filter also for create_timestamp??
        $filter->addFilter(new Tinebase_Model_Filter_DateTime(
            'last_modified_time',
            'after',
            $startTimeStamp
        ));
        
        if ($endTimeStamp !== NULL) {
            $filter->addFilter(new Tinebase_Model_Filter_DateTime(
                'last_modified_time',
                'before',
                $endTimeStamp
            ));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " Assembled {$this->_contentFilterClass}: " . print_r($filter->toArray(), TRUE));

        $this->_assertContentControllerParams($folderId);
        $result = $this->_contentController->search($filter, NULL, false, true, 'sync');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Found " . count($result) . ' changed record(s).');
        
        return $result;
    }
    
    /**
     * retrieve folders which were modified since last sync
     * 
     * @param  DateTime $startTimeStamp
     * @param  DateTime $endTimeStamp
     * @return array
     */
    public function getChangedFolders(DateTime $startTimeStamp, DateTime $endTimeStamp)
    {
        $syncrotonFolders = array();
        
        if (! $this->_deviceSupportsMultipleFolders()) {
            return $syncrotonFolders;
        }
        
        $folders = $this->_getAllFolders();
        
        foreach ($folders as $folder) {
            if (! ($folder->last_modified_time > $startTimeStamp && $folder->last_modified_time <= $endTimeStamp)) {
                $folders->removeRecord($folder);
            }
        }
        
        foreach ($folders as $container) {
            $syncrotonFolders[$container->id] = $this->_convertContainerToSyncrotonFolder($container);
        }
        
        return $syncrotonFolders;
    }

    /**
     * @return bool
     */
    protected function _deviceSupportsMultipleFolders()
    {
        // NOTE: android is quite a devicetype zoo. we tired to enable all devices having 'android' in the
        //       OS string. But it didn't work - e.g. samsungsma310f (Samsung A3 (6)) has no folder support
        return in_array(strtolower($this->_device->devicetype), $this->_getDevicesWithMultipleFolders());
    }
    
    /**
     * return recordset with all folders
     * 
     * @return Tinebase_Record_RecordSet
     */
    protected function _getAllFolders()
    {
        // get the folders the user has access to
        $allowedFolders = $this->_getSyncableFolders();
        
        $wantedFolders = null;
        
        // check if contentfilter has a container limitation
        $filter = $this->_getContentFilter(0);
        
        $containerFilters = $filter->getFilter('container_id', TRUE, TRUE);
        if ($containerFilters) {
            $wantedFolders = [];
            foreach ($containerFilters as $containerFilter) {
                if ($containerFilter instanceof Tinebase_Model_Filter_Container) {
                    $containerFilter->setRequiredGrants(array(Tinebase_Model_Grants::GRANT_SYNC));
                    $wantedFolders = array_merge($wantedFolders,$containerFilter->getContainerIds());
                }
            }
            $wantedFolders = array_unique($wantedFolders);

            foreach($allowedFolders as $allowedFolder) {
                if (! in_array($allowedFolder->getId(), $wantedFolders)) {
                    $allowedFolders->removeRecord($allowedFolder);
                }
            }
        }
        
        return $allowedFolders;
    }

    /**
     * 
     * @return int     Syncroton_Command_Sync::FILTER...
     */
    public function getMaxFilterType()
    {
        return Syncroton_Command_Sync::FILTER_NOTHING;
    }

    /**
     * 
     * @param  string  $folderId
     * @param  int     $filterType
     * @return Tinebase_Record_RecordSet
     */
    public function getServerEntries($folderId, $filterType)
    {
        $maxFilterType = $this->getMaxFilterType();
        if ($maxFilterType !== Syncroton_Command_Sync::FILTER_NOTHING) {
            if ($filterType === Syncroton_Command_Sync::FILTER_NOTHING || $maxFilterType < $filterType) {
                $filterType = $maxFilterType;
            }
        }

        $filter = $this->_getContentFilter($filterType);
        $this->_addContainerFilter($filter, $folderId);
        
        if(!empty($this->_sortField)) {
            $pagination = new Tinebase_Model_Pagination(array(
                'sort' => $this->_sortField
            ));
        } else {
            $pagination = null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " Assembled {$this->_contentFilterClass}: " . print_r($filter->toArray(), TRUE));
        
        $result = $this->_contentController->search($filter, $pagination, false, true, 'sync');
        
        return $result;
    }
    
    /**
     * inspect getCountOfChanges
     * 
     * @param Syncroton_Backend_IContent  $contentBackend
     * @param Syncroton_Model_IFolder     $folder
     * @param Syncroton_Model_ISyncState  $syncState
     */
    protected function _inspectGetCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        // does nothing by default
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::getCountOfChanges()
     */
    public function getCountOfChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        $this->_inspectGetCountOfChanges($contentBackend, $folder, $syncState);
        
        $allClientEntries = $contentBackend->getFolderState($this->_device, $folder);
        $allServerEntries = $this->getServerEntries($folder->serverId, $folder->lastfiltertype);
        
        $addedEntries       = array_diff($allServerEntries, $allClientEntries);
        $deletedEntries     = array_diff($allClientEntries, $allServerEntries);
        $changedEntries     = $this->getChangedEntries($folder->serverId, $syncState->lastsync);
        
        return count($addedEntries) + count($deletedEntries) + count($changedEntries);
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IData::hasChanges()
     */
    public function hasChanges(Syncroton_Backend_IContent $contentBackend, Syncroton_Model_IFolder $folder, Syncroton_Model_ISyncState $syncState)
    {
        return !!$this->getCountOfChanges($contentBackend, $folder, $syncState);
    }
     
    /**
    
    /**
     * return (outer) contentfilter array
     * 
     * @param  int $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = new $this->_contentFilterClass();
        
        try {
            $persistentFilterId = $this->_device->{$this->_filterProperty};
            if ($persistentFilterId) {
                $filter = Tinebase_PersistentFilter::getFilterById($persistentFilterId);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            // filter got deleted already
        }
        
        return $filter;
    }
    
    /**
     * return true there is memory $needed left
     *  
     * @param int $needed
     * @return boolean
     */
    protected function _isMemoryLeft($needed)
    {
        if (ini_get('memory_limit') == -1) {
            return true;
        }
        
        // calculate with an overhead of 1.2
        if (Tinebase_Helper::convertToBytes(ini_get('memory_limit')) > memory_get_usage(TRUE) + ($needed * 1.2)) {
            return true;
        }
        
        return false;
    }

    /**
     * template function to assert content controller params
     * @param $folderId
     */
    protected function _assertContentControllerParams($folderId)
    {

    }

    /**
     * convert contact from xml to Tinebase_Record_Interface
     *
     * @param Syncroton_Model_IEntry $data
     * @param Tinebase_Record_Interface $entry
     * @return Tinebase_Record_Interface
     */
    abstract public function toTineModel(Syncroton_Model_IEntry $data, $entry = null);
    
    abstract public function toSyncrotonModel($entry, array $options = array());

    /**
     * @param mixed $value
     * @return bool
     */
    protected function _isEmptyValue($value)
    {
        return empty($value) && $value != '0'
            || is_array($value) && count($value) === 0;
    }
}
