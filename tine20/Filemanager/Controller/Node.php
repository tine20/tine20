<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add transactions to move/create/delete/copy 
 */

/**
 * Node controller for Filemanager
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller_Node extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * Filesystem backend
     *
     * @var Tinebase_FileSystem
     */
    protected $_backend = NULL;
    
    /**
     * the model handled by this controller
     * @var string
     */
    protected $_modelName = 'Filemanager_Model_Node';

    /**
     * @var boolean
     */
    protected $_omitModLog = false;
    
    /**
     * holds the total count of the last recursive search
     * @var integer
     */
    protected $_recursiveSearchTotalCount = 0;

    /**
     * recursion check for create modlog inside copy / move
     *
     * @var bool
     */
    protected $_inCopyOrMoveNode = false;

    /**
     * where to throw on access to a quarantined node or not
     *
     * @var bool
     */
    protected $_throwOnGetQuarantined = true;
    
    /**
     * holds the instance of the singleton
     *
     * @var Filemanager_Controller_Node
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_resolveCustomFields = true;
        $this->_backend = Tinebase_FileSystem::getInstance();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Filemanager_Controller_Node
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller_Node();
        }
        
        return self::$_instance;
    }

    /**
     * get/set whether to throw on access on quarantined file
     *
     * @param null|boolean $_val
     * @return bool
     */
    public function doThrowOnGetQuarantined($_val = null)
    {
        $result = $this->_throwOnGetQuarantined;
        if (null !== $_val) {
            $this->_throwOnGetQuarantined = (bool)$_val;
        }
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::update()
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        // be careful, don't put $_record in here, like that parent_id might be spoofed! It must be only the id!
        $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(
            $this->_backend->getPathOfNode($_record->getId(), true));

        // we allow only notification updates for the current user itself if not admin right
        if (! $this->_backend->checkPathACL($path, 'admin', true, false)) {
            $this->_backend->checkPathACL($path, 'get');

            $usersNotificationSettings = null;
            $currentUserId = Tinebase_Core::getUser()->getId();
            foreach ($_record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION) as $xpNotification) {
                if (isset($xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) &&
                        isset($xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE]) &&
                        Tinebase_Acl_Rights::ACCOUNT_TYPE_USER === $xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE] &&
                        $currentUserId ===  $xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) {
                    $usersNotificationSettings = $xpNotification;
                    break;
                }
            }

            $currentRecord = $this->get($_record->getId());

            if (! $this->_backend->checkPathACL($path, 'update', true, false)) {
                // we reset all input and then just apply the notification settings for the current user
                $_record = $currentRecord;
                $hasUpdateGrant = false;
            } else {
                // we just reset the notification settings
                $_record->{Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION} = $currentRecord->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION);
                $hasUpdateGrant = true;
            }

            $found = false;
            foreach ($_record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION) as $key => &$xpNotification) {
                if (isset($xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) &&
                        isset($xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE]) &&
                        Tinebase_Acl_Rights::ACCOUNT_TYPE_USER === $xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE] &&
                        $currentUserId ===  $xpNotification[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) {
                    if (null !== $usersNotificationSettings) {
                        $xpNotification = $usersNotificationSettings;
                    } else {
                        unset($_record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)[$key]);
                    }
                    $found = true;
                    break;
                }
            }
            if (false === $found && null !== $usersNotificationSettings) {
                $_record->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)[] = $usersNotificationSettings;
            }

            if (false === $hasUpdateGrant && false === $found && null === $usersNotificationSettings){
                throw new Tinebase_Exception_AccessDenied('No permission to update nodes.');
            }
        }

        return parent::update($_record, $_duplicateCheck);
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Filemanager_Model_Node $_record      the update record
     * @param   Filemanager_Model_Node $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // protect against file object spoofing
        foreach (array_keys($_record->toArray()) as $property) {
            if (! in_array($property, array('name', 'description', 'relations', 'customfields', 'tags', 'notes', 'acl_node', 'grants', 'quota', Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION, Tinebase_Model_Tree_Node::XPROPS_REVISION, 'pin_protected_node'))) {
                $_record->{$property} = $_oldRecord->{$property};
            }
        }

        if (!Tinebase_Core::getUser()->hasGrant($_record, Tinebase_Model_Grants::GRANT_ADMIN, 'Tinebase_Model_Tree_Node')) {
            $_record->{Tinebase_Model_Tree_Node::XPROPS_REVISION} = $_oldRecord->{Tinebase_Model_Tree_Node::XPROPS_REVISION};
            $_record->quota = $_oldRecord->quota;
            $_record->pin_protected_node = $_oldRecord->pin_protected_node;
        } elseif ($_record->pin_protected_node !== $_oldRecord->pin_protected_node) {
            if ($_record->pin_protected_node !== $_record->getId() && !empty($_record->pin_protected_node)) {
                throw new Tinebase_Exception_InvalidArgument(
                    'pin_protected_node may only be set to its own node id or to null');
            }
            if (empty($_record->pin_protected_node)) {
                $_record->pin_protected_node = null;
            }
        }

        $aclNode = $this->_updateNodeAcl($_record, $_oldRecord);

        // reset node acl value to prevent spoofing
        $_record->acl_node = $aclNode;
    }

    /**
     * update node acl
     *
     * @param $record
     * @param $oldRecord
     * @return string
     */
    protected function _updateNodeAcl($record, $oldRecord)
    {
        $aclNode = $oldRecord->acl_node;

        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER !== $record->type) {
            return $aclNode;
        }

        $currentUser = Tinebase_Core::getUser();
        if (! $currentUser->hasGrant(
            $record,
            Tinebase_Model_Grants::GRANT_ADMIN,
            'Tinebase_Model_Tree_Node')
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Update node ACL requires ADMIN grant');
            return $aclNode;
        }

        $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_backend->getPathOfNode($record->getId(), true));
        if ($nodePath->isSystemPath()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Could not update ACL for system path');
            return $aclNode;
        }

        if (empty($record->acl_node) && !$nodePath->isToplevelPath()) {
            // acl_node empty -> remove acl
            $node = $this->_backend->setAclFromParent($nodePath->statpath, true);
            $aclNode = $node->acl_node;

        } elseif ($record->acl_node === $record->getId() && isset($record->grants)) {
            $oldGrants = Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($oldRecord);
            if (is_array($record->grants)) {
                $record->grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $record->grants);
            }
            $diff = $record->grants->diff($oldGrants);
            if (!$diff->isEmpty() || $oldRecord->acl_node !== $record->acl_node) {
                $stillAdmin = false;
                /** @var Tinebase_Model_Grants $grant */
                foreach ($record->grants as $grant) {
                    if ($grant->userHasGrant(Tinebase_Model_Grants::GRANT_ADMIN, $currentUser)) {
                        $stillAdmin = true;
                        break;
                    }
                }
                if (!$stillAdmin) {
                    throw new Tinebase_Exception_SystemGeneric('you can\'t remove your own admin grant'); // _("you can't remove your own admin grant")
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Setting new node grants.');
                $this->_backend->setGrantsForNode($record, $record->grants);
            }
            $aclNode = $record->acl_node;
        }

        return $aclNode;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::getMultiple()
     *
     * @param array $_ids
     * @param bool $_ignoreACL
     * @param null|Tinebase_Record_Expander $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        foreach (($results = $this->_backend->getMultipleTreeNodes($_ids, $_ignoreACL)) as $node) {
            $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(
                $this->_backend->getPathOfNode($node->getId(), true));
            if (! $this->_backend->checkPathACL($path, 'get', true, false)) {
                $results->removeRecord($node);
            }
        }
        $this->resolveMultipleTreeNodesPath($results);
        return $results;
    }
    
    /**
     * Resolve path of multiple tree nodes
     * 
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     */
    public function resolveMultipleTreeNodesPath($_records)
    {
        $records = ($_records instanceof Tinebase_Model_Tree_Node)
            ? new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($_records)) : $_records;
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Resolving paths for ' . count($records) .  ' records.');
            
        foreach ($records as $record) {
            $path = $this->_backend->getPathOfNode($record, TRUE);
            $record->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($path, $this->_applicationName);

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Got path ' . $record->path .  ' for node ' . $record->name);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::get()
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = true, $_getDeleted = false)
    {
        /** @var Tinebase_Model_Tree_Node $record */
        $record = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted);

        if ($record->is_quarantined) {
            throw new Filemanager_Exception_Quarantined('File is quarantined');
        }

        $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_backend->getPathOfNode($record, true));

        $this->_backend->checkPathACL($nodePath, 'get');

        $record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Tinebase_Model_Tree_Node::class, $record->getId());


        $record->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($nodePath->flatpath, $this->_applicationName);
        $this->resolveGrants($record);

        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $record->type) {
            $context = $this->getRequestContext();
            if (is_array($context) && isset($context['quotaResult'])) {
                $context['quotaResult'] = $this->_backend->getEffectiveAndLocalQuota($record);
                $this->setRequestContext($context);
            }
        }

        return $record;
    }
    
    /**
     * search tree nodes
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string|optional $_action
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        // perform recursive search on recursive filter set
        if ($_filter->isRecursiveFilter()) {
            return $this->_searchNodesRecursive($_filter, $_pagination);
        } else {
            $path = $this->_checkFilterACL($_filter, $_action);
        }
        
        if ($path->containerType === Tinebase_Model_Tree_Node_Path::TYPE_ROOT) {
            $result = $this->_getRootNodes();
        } elseif ($path->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL && ! $path->containerOwner) {
            if (! $this->_backend->fileExists($path->statpath)) {
                $this->_backend->mkdir($path->statpath);
            }
            $result = $this->_getOtherUserNodes();
            $this->resolvePath($result, $path);
        } else {
            try {
                $result = $this->_backend->searchNodes($_filter, $_pagination);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create basic nodes like personal|shared|user root
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ' ' . $path->statpath);
                if ($path->name === Tinebase_FileSystem::FOLDER_TYPE_SHARED ||
                    $path->statpath === $this->_backend->getApplicationBasePath(
                        Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName), 
                        Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
                    ) . '/' . Tinebase_Core::getUser()->getId()
                ) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ' Creating new path ' . $path->statpath);
                    $this->_backend->mkdir($path->statpath);
                    $result = $this->_backend->searchNodes($_filter, $_pagination);
                } else {
                    throw $tenf;
                }
            }
            $this->resolvePath($result, $path);
        }

        $parentNode = $this->_backend->stat($path->statpath);
        $quota = $this->_backend->getEffectiveAndLocalQuota($parentNode);
        $context = $this->getRequestContext();
        if (!is_array($context)) {
            $context = array();
        }
        $context['quotaResult'] = $quota;
        /** @var Tinebase_Model_Tree_Node_Filter $_filter */
        $_filter->ignorePinProtection();
        if ((int)$this->_backend->searchNodesCount($_filter) !== $result->count()) {
            $context['pinProtectedData'] = true;
        }
        $this->setRequestContext($context);
        $_filter->ignorePinProtection(false);

        $this->resolveGrants($result);
        return $result;
    }
    
    /**
     * search tree nodes for search combo
     * 
     * @param Tinebase_Model_Tree_Node_Filter $_filter
     * @param Tinebase_Record_Interface $_pagination
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    
    protected function _searchNodesRecursive($_filter, $_pagination)
    {
        $_filter->removeFilter('type');
        $_filter->addFilter($_filter->createFilter('type', 'equals', Tinebase_Model_Tree_FileObject::TYPE_FILE));
        $filter = clone $_filter;
        // prepend base path to original $_filter object! it is required for toArray() in the response array
        $this->_ensurePathFilterPresent($_filter);
        $filter->removeFilter('path');
        $filter->removeFilter('recursive');

        $filter = new Tinebase_Model_Tree_Node_Filter($filter->toArray(), '', array('nameCaseInSensitive' => true));

        $result = $this->_backend->searchNodes($filter, $_pagination);

        // resolve path
        $parents = array();
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $appPath = '/' . $app->getId() . '/' . Tinebase_Model_Tree_Node_Path::FOLDERS_PART;
        $appPathLen = strlen($appPath);

        /** @var Tinebase_Model_Tree_Node $fileNode */
        foreach($result as $fileNode) {
            if (!isset($parents[$fileNode->parent_id])) {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_backend->getPathOfNode($this->_backend->get($fileNode->parent_id), true));
                if (strpos($path, $appPath) === 0) {
                    $parents[$fileNode->parent_id] = substr($path, $appPathLen);
                } else {
                    $parents[$fileNode->parent_id] = false;

                }
            }

            if ($parents[$fileNode->parent_id] === false) {
                $result->removeRecord($fileNode);
            } else {
                $fileNode->path = $parents[$fileNode->parent_id] . '/' . $fileNode->name;
            }
        }

        $filter->ignorePinProtection();
        if ((int)$this->_backend->searchNodesCount($filter) !== $result->count()) {
            $context = $this->getRequestContext();
            if (!is_array($context)) {
                $context = array();
            }
            $context['pinProtectedData'] = true;
            $this->setRequestContext($context);
        }

        $this->resolveGrants($result);
        
        return $result;
    }
    
    /**
     * checks filter acl and adds base path
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $path = $this->_ensurePathFilterPresent($_filter);
        
        $this->_backend->checkPathACL($path, $_action);
        
        return $path;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return Tinebase_Model_Tree_Node_Path
     */
    protected function _ensurePathFilterPresent(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        if ($_filter === NULL) {
            $_filter = new Tinebase_Model_Tree_Node_Filter();
        }

        $pathFilters = $_filter->getFilter('path', TRUE);
        if (count($pathFilters) !== 1) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . 'Exactly one path filter required.');
            $pathFilter = (count($pathFilters) > 1) ? $pathFilters[0] : new Tinebase_Model_Tree_Node_PathFilter(array(
                    'field'     => 'path',
                    'operator'  => 'equals',
                    'value'     => '/',)
            );
            $_filter->removeFilter('path');
            $_filter->addFilter($pathFilter);
        } else {
            $pathFilter = $pathFilters[0];
        }

        // check path is valid or reset it to root aka /
        try {
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($pathFilter->getValue());
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Could not determine path, setting root path (' . $e->getMessage() . ')');
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath('/'));
            $pathFilter->setValue($path);
        }

        return $path;
    }
    
    /**
     * get the three root nodes
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     *
     * TODO think about using the "real" ids instead of myUser/other/shared
     */
    protected function _getRootNodes()
    {
        $translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(
            array(
                'name'   => $translate->_('My folders'),
                'path'   => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName,
                'type'   => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                'id'     => 'myUser',
                'grants' => array(),
            ),
            array(
                'name' => $translate->_('Shared folders'),
                'path' => '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED,
                'type' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                'id' => Tinebase_FileSystem::FOLDER_TYPE_SHARED,
                'grants' => array(),
            ),
            array(
                'name' => $translate->_('Other users folders'),
                'path' => '/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL,
                'type' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                'id' => Tinebase_Model_Container::TYPE_OTHERUSERS,
                'grants' => array(),
            ),
        ), TRUE); // bypass validation
        
        return $result;
    }

    /**
     * get other users nodes
     * 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getOtherUserNodes()
    {
        $result = $this->_backend->getOtherUsers(Tinebase_Core::getUser(), $this->_modelName, Tinebase_Model_Grants::GRANT_READ);
        return $result;
    }

    /**
     * sort nodes (only checks if we are on the container level and sort by container_name then)
     *
     * @param Tinebase_Record_RecordSet $nodes
     * @param Tinebase_Model_Tree_Node_Path $path
     * @param Tinebase_Model_Pagination $pagination
     *
     * TODO still needed?
     */
    protected function _sortContainerNodes(Tinebase_Record_RecordSet $nodes, Tinebase_Model_Tree_Node_Path $path, Tinebase_Model_Pagination $pagination = NULL)
    {
//        if ($path->container || ($pagination !== NULL && $pagination->sort && $pagination->sort !== 'name')) {
//            // no toplevel path or no sorting by name -> sorting should be already handled by search()
//            return;
//        }
//
//        $dir = ($pagination !== NULL && $pagination->dir) ? $pagination->dir : 'ASC';
//
//        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
//            . ' Sorting container nodes by name (path: ' . $path->flatpath . ') / dir: ' . $dir);
//
//        $nodes->sort('container_name', $dir);
    }

    /**
     * get file node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param integer|null $_revision
     * @return Tinebase_Model_Tree_Node
     */
    public function getFileNode(Tinebase_Model_Tree_Node_Path $_path, $_revision = null)
    {
        $this->_backend->checkPathACL($_path, 'get');
        
        if (! $this->_backend->fileExists($_path->statpath, $_revision)) {
            throw new Filemanager_Exception('File does not exist,');
        }
        
        if (! $this->_backend->isFile($_path->statpath)) {
            throw new Filemanager_Exception('Is a directory');
        }
        
        $node = $this->_backend->stat($_path->statpath, $_revision);
        if ($node->is_quarantined) {
            throw new Filemanager_Exception_Quarantined('File is quarantined');
        }

        return $node;
    }
    
    /**
     * add base path
     * 
     * @param string $_path
     * @return string
     */
    public function addBasePath($_path)
    {
        $basePath = $this->_backend->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
        $basePath .= '/folders';
        
        $path = (strpos($_path, '/') === 0) ? $_path : '/' . $_path;
        // only add base path once
        $result = strpos($path, $basePath) !== 0 ? $basePath . $path : $path;
        
        return $result;
    }

    /**
     * @param $_path
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     *
     * TODO should be removed/replaced
     */
    public function removeBasePath($_path)
    {
        $basePath = $this->_backend->getApplicationBasePath(Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
        $basePath .= '/folders';

        return preg_replace('@^' . preg_quote($basePath, '@') . '@', '', $_path);
    }

    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string|optional $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($_filter->getFilter('recursive')) {
            $filter = clone $_filter;
            $filter->removeFilter('path');
            $filter->removeFilter('recursive');
            $filter->removeFilter('type');
            $filter->addFilter($filter->createFilter('type', 'equals', Tinebase_Model_Tree_FileObject::TYPE_FILE));
            $filter = new Tinebase_Model_Tree_Node_Filter($filter->toArray(), '', array('nameCaseInSensitive' => true));
            $result = $this->_backend->searchNodesCount($filter);
        } else {
            $path = $this->_checkFilterACL($_filter, $_action);
            if ($path->containerType === Tinebase_Model_Tree_Node_Path::TYPE_ROOT) {
                $result = count($this->_getRootNodes());
            } else if ($path->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL && !$path->containerOwner) {
                $result = count($this->_getOtherUserNodes());
            } else {
                $result = $this->_backend->searchNodesCount($_filter);
            }
        }
        
        return $result;
    }

    /**
     * create node(s)
     * 
     * @param array $_filenames
     * @param array $_types directory or file
     * @param array $_tempFileIds
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     * @throws Filemanager_Exception_NodeExists
     */
    public function createNodes($_filenames, $_types, $_tempFileIds = array(), $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $nodeExistsException = NULL;
        if (!is_array($_filenames)) $_filenames = [$_filenames];
        if (!is_array($_types)) $_types = array_fill(0, count($_filenames), $_types);
        
        foreach ($_filenames as $idx => $filename) {
            $tempFileId = (isset($_tempFileIds[$idx])) ? $_tempFileIds[$idx] : NULL;
            $type = isset($_types[$idx]) ? $_types[$idx] : NULL;

            try {
                $node = $this->_createNode($filename, $type, $tempFileId, $_forceOverwrite);
                if ($node) {
                    $result->addRecord($node);
                }
            } catch (Filemanager_Exception_NodeExists $fene) {
                $nodeExistsException = $this->_handleNodeExistsException($fene, $nodeExistsException);
            }
        }

        if ($nodeExistsException) {
            throw $nodeExistsException;
        }
        
        return $result;
    }
    
    /**
     * collect information of a Filemanager_Exception_NodeExists in a "parent" exception
     * 
     * @param Filemanager_Exception_NodeExists $_fene
     * @param Filemanager_Exception_NodeExists|NULL $_parentNodeExistsException
     * @return Filemanager_Exception_NodeExists
     */
    protected function _handleNodeExistsException($_fene, $_parentNodeExistsException = NULL)
    {
        // collect all nodes that already exist and add them to exception info
        if (! $_parentNodeExistsException) {
            $_parentNodeExistsException = new Filemanager_Exception_NodeExists();
        }
        
        $nodesInfo = $_fene->getExistingNodesInfo();
        if (count($nodesInfo) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Adding node info to exception.');
            $_parentNodeExistsException->addExistingNodeInfo($nodesInfo->getFirstRecord());
        } else {
            return $_fene;
        }
        
        return $_parentNodeExistsException;
    }
    
    /**
     * create new node
     * 
     * @param string|Tinebase_Model_Tree_Node_Path $_path
     * @param string $_type
     * @param string $_tempFileId
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Filemanager_Exception_NodeExists
     * @throws Tinebase_Exception_NotFound
     */
    protected function _createNode($_path, $_type, $_tempFileId = NULL, $_forceOverwrite = FALSE)
    {
        $path = ($_path instanceof Tinebase_Model_Tree_Node_Path)
            ? $_path : Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($_path));
        $parentPathRecord = $path->getParent();
        $existingNode = null;
        
        // we need to check the parent record existence before commencing node creation

        try {
            $parentPathRecord->validateExistance();
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($parentPathRecord->isToplevelPath()) {
                $this->_backend->mkdir($parentPathRecord->statpath);
            } else {
                throw $tenf;
            }
        }
        
        try {
            $this->_checkIfExists($path);
            $this->_backend->checkPathACL($parentPathRecord, 'add', /* $_topLevelAllowed */ $_type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        } catch (Filemanager_Exception_NodeExists $fene) {
            if ($_forceOverwrite) {

                // race condition for concurrent delete, try catch Tinebase_Exception_NotFound ... but throwing the exception in that rare case doesn't hurt so much
                $existingNode = $this->_backend->stat($path->statpath);
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Existing node: ' . print_r($existingNode->toArray(), TRUE));

                if (! $_tempFileId) {
                    // just return the exisiting node and do not overwrite existing file if no tempfile id was given
                    $this->_backend->checkPathACL($path, 'get');
                    $this->resolvePath($existingNode, $parentPathRecord);
                    $this->resolveGrants($existingNode);
                    return $existingNode;

                } elseif ($existingNode->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER && $_type !==
                        Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                    throw new Tinebase_Exception_SystemGeneric('Can not overwrite a folder with a file');

                }  elseif ($existingNode->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER && $_type ===
                    Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                    throw new Tinebase_Exception_SystemGeneric('Can not overwrite a file with a folder');

                } else {
                    // check if a new (size 0) file is overwritten
                    // @todo check revision here?
                    if ($existingNode->size == 0) {
                        $this->_backend->checkPathACL($parentPathRecord, 'add');
                    } else {
                        $this->_backend->checkPathACL($parentPathRecord, 'update');
                    }
                }
            } else if (! $_forceOverwrite) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' ' . $fene);
                throw $fene;
            }
        }

        $newNodePath = $parentPathRecord->statpath . '/' . $path->name;
        $newNode = $this->_createNodeInBackend($newNodePath, $_type, $_tempFileId);

        $this->resolvePath($newNode, $parentPathRecord);
        $this->resolveGrants($newNode);
        return $newNode;
    }
    
    /**
     * create node in backend
     * 
     * @param string $_statpath
     * @param type
     * @param string $_tempFileId
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _createNodeInBackend($_statpath, $_type, $_tempFileId = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Creating new path ' . $_statpath . ' of type ' . $_type);

        $node = NULL;
        switch ($_type) {
            case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath($_statpath);
                if ($path->getParent()->isToplevelPath()) {
                    $node = $this->_backend->createAclNode($_statpath);
                } else {
                    $node = $this->_backend->mkdir($_statpath);
                }
                break;

            default:
                if ($_type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                    $_type = null;
                }
                if (null === $_tempFileId) {
                    $this->_backend->createFileTreeNode($this->_backend->stat(dirname($_statpath)),
                        basename($_statpath), Tinebase_Model_Tree_FileObject::TYPE_FILE, $_type);
                } else {
                    try {
                        $this->_backend->copyTempfile($_tempFileId, $_statpath, true);
                    } catch (Tinebase_Exception_Record_NotAllowed $e) {
                        if ('quota exceeded' === $e->getMessage()) {
                            $this->_backend->unlink($_statpath);
                        }
                        throw $e;
                    }
                }
                break;
        }

        return $node !== null ? $node : $this->_backend->stat($_statpath);
    }
    
    /**
     * check file existence
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param Tinebase_Model_Tree_Node $_node
     * @throws Filemanager_Exception_NodeExists
     */
    protected function _checkIfExists(Tinebase_Model_Tree_Node_Path $_path, $_node = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Check existance of ' . $_path->statpath);

        if ($this->_backend->fileExists($_path->statpath)) {
            
            if (! $_node) {
                $_node = $this->_backend->stat($_path->statpath);
            }
            
            if ($_node) {
                $existsException = new Filemanager_Exception_NodeExists();
                $existsException->addExistingNodeInfo($_node);
                throw $existsException;
            }
        }
    }
    
    /**
     * create new container
     * 
     * @param string $_name
     * @param string $_type
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _createContainer($_name, $_type)
    {
        $ownerId = ($_type === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) ? Tinebase_Core::getUser()->getId() : NULL;
        try {
            $existingContainer = Tinebase_Container::getInstance()->getContainerByName(Filemanager_Model_Node::class,
                $_name, $_type, $ownerId);
            throw new Filemanager_Exception_NodeExists('Container ' . $_name . ' of type ' . $_type . ' already exists.');
        } catch (Tinebase_Exception_NotFound $tenf) {
            // go on
        }
        
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $_name,
            'type'           => $_type,
            'backend'        => 'sql',
            'application_id' => $app->getId(),
            'model'          => $this->_modelName
        )));
        
        return $container;
    }

    /**
     * resolve node paths for frontends
     *
     * if a single record is given, use the resulting record set, because the referenced record is no longer updated!
     *
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @return Tinebase_Record_RecordSet
     */
    public function resolvePath($_records, Tinebase_Model_Tree_Node_Path $_path)
    {
        $records = ($_records instanceof Tinebase_Model_Tree_Node) 
            ? new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($_records)) : $_records;

        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $flatpathWithoutBasepath = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_path->flatpath, $app);
        if ($records) {
            foreach ($records as $record) {
                $record->path = $flatpathWithoutBasepath . '/' . $record->name;
            }
        }

        return $records;
    }

    /**
     * @param Tinebase_Record_RecordSet|Tinebase_Model_Tree_Node $_records
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_NotFound
     */
    public function resolveGrants($_records)
    {
        $records = ($_records instanceof Tinebase_Model_Tree_Node)
            ? new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($_records)) : $_records;
        if ($records) {
            foreach ($records as $record) {
                $grantNode = $this->_getGrantNode($record);
                $record->account_grants = $this->_backend->getGrantsOfAccount(
                    Tinebase_Core::getUser(),
                    $grantNode
                )->toArray();
                if (! isset($record->grants)) {
                    try {
                        $record->grants = Tinebase_FileSystem::getInstance()->getGrantsOfContainer($record);
                    } catch (Tinebase_Exception_AccessDenied $tead) {
                        $record->grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
                    }
                }
            }
        }

        return $records;
    }

    /**
     * @param Tinebase_Model_Tree_Node $record
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getGrantNode(Tinebase_Model_Tree_Node $record)
    {
        try {
            switch ($record->getId()) {
                case 'myUser':
                    $path = $this->_backend->getApplicationBasePath($this->_applicationName, Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
                    $path .= '/' . Tinebase_Core::getUser()->getId();
                    $grantRecord = $this->_backend->stat($path);
                    break;
                case Tinebase_FileSystem::FOLDER_TYPE_SHARED:
                    $path = $this->_backend->getApplicationBasePath($this->_applicationName, Tinebase_FileSystem::FOLDER_TYPE_SHARED);
                    $grantRecord = $this->_backend->stat($path);
                    break;
                case Tinebase_Model_Container::TYPE_OTHERUSERS:
                    $path = $this->_backend->getApplicationBasePath($this->_applicationName, Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
                    $grantRecord = $this->_backend->stat($path);
                    break;
                default:
                    $grantRecord = clone($record);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (isset($path)) {
                try {
                    $grantRecord = $this->_backend->createAclNode($path);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    // some strange race condition ...
                    throw $tenf;
                }
            } else {
                throw $tenf;
            }
        }

        return $grantRecord;
    }

    /**
     * copy nodes
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function copyNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'copy', $_forceOverwrite);
    }
    
    /**
     * copy or move an array of nodes identified by their path
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param string $_action copy|move
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, $_action, $_forceOverwrite = FALSE)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $nodeExistsException = NULL;

        $this->_inCopyOrMoveNode = true;
        
        foreach ($_sourceFilenames as $idx => $source) {
            $sourcePathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($source));
            $destinationPathRecord = $this->_getDestinationPath($_destinationFilenames, $idx, $sourcePathRecord);
            
            if ($this->_backend->fileExists($destinationPathRecord->statpath) && $sourcePathRecord->flatpath === $destinationPathRecord->flatpath) {
                throw new Filemanager_Exception_DestinationIsSameNode();
            }
            
            // test if destination is subfolder of source
            $dest = explode('/', $destinationPathRecord->statpath);
            $source = explode('/', $sourcePathRecord->statpath);
            $isSub = TRUE;

            $i = 0;
            for ($iMax = count($source); $i < $iMax; $i++) {
                
                if (! isset($dest[$i])) {
                    break;
                }
                
                if ($source[$i] != $dest[$i]) {
                    $isSub = FALSE;
                }
            }
            if ($isSub) {
                throw new Filemanager_Exception_DestinationIsOwnChild();
            }
            
            try {
                if ($_action === 'move') {
                    $node = $this->_moveNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                } else if ($_action === 'copy') {
                    $node = $this->_copyNode($sourcePathRecord, $destinationPathRecord, $_forceOverwrite);
                }

                if ($node instanceof Tinebase_Record_Interface) {
                    $result->addRecord($node);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Could not copy or move node to destination ' . $destinationPathRecord->flatpath);
                }
            } catch (Filemanager_Exception_NodeExists $fene) {
                $this->_inCopyOrMoveNode = false;
                $nodeExistsException = $this->_handleNodeExistsException($fene, $nodeExistsException);
            }
        }
        
        $this->resolvePath($result, $destinationPathRecord->getParent());
        $this->resolveGrants($result);

        if ($nodeExistsException) {
            // @todo add correctly moved/copied files here?
            throw $nodeExistsException;
        }

        $this->_inCopyOrMoveNode = false;
        
        return $result;
    }
    
    /**
     * get single destination from an array of destinations and an index + $_sourcePathRecord
     * 
     * @param string|array $_destinationFilenames
     * @param int $_idx
     * @param Tinebase_Model_Tree_Node_Path $_sourcePathRecord
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Filemanager_Exception
     * 
     * @todo add Tinebase_FileSystem::isDir() check?
     */
    protected function _getDestinationPath($_destinationFilenames, $_idx, $_sourcePathRecord)
    {
        if (is_array($_destinationFilenames)) {
            $isdir = FALSE;
            if (isset($_destinationFilenames[$_idx])) {
                $destination = $_destinationFilenames[$_idx];
            } else {
                throw new Filemanager_Exception('No destination path found.');
            }
        } else {
            // borken!!! should be refactored. Side effects? Who knows...
            $isdir = TRUE;
            $destination = $_destinationFilenames;
        }
        
        if ($isdir) {
            $destination = $destination . '/' . $_sourcePathRecord->name;
        }
        
        return Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($destination));
    }
    
    /**
     * copy single node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _copyNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_forceOverwrite = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Copy Node ' . $_source->flatpath . ' to ' . $_destination->flatpath);
                
        $newNode = NULL;
        
        $this->_backend->checkPathACL($_source, 'get', FALSE);
        
        $sourceNode = $this->_backend->stat($_source->statpath);
        
        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_FileObject::TYPE_FILE:
                $newNode = $this->_copyOrMoveFileNode($_source, $_destination, 'copy', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                $newNode = $this->_copyFolderNode($_source, $_destination);
                break;
        }
        
        return $newNode;
    }
    
    /**
     * copy file node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param string $_action
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Filemanager_Exception_NodeExists
     */
    protected function _copyOrMoveFileNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_action, $_forceOverwrite = FALSE)
    {
        $this->_backend->checkPathACL($_destination->getParent(), 'update', FALSE);
        
        try {
            $this->_checkIfExists($_destination);
            // check if there is a node our user can't see
            if (null !== $this->_backend->_getTreeNodeBackend()->getChild($_destination->getParent()->getNode(),
                    $_destination->name, false, false)) {
                throw new Tinebase_Exception_SystemGeneric('The destination already exists, but you don\'t have the right to see it.');
            }
        } catch (Filemanager_Exception_NodeExists $fene) {
            if ($_forceOverwrite && $_source->statpath !== $_destination->statpath) {
                // delete old node
                $this->_backend->unlink($_destination->statpath);
            } elseif (! $_forceOverwrite) {
                throw $fene;
            }
        }
        
        switch ($_action) {
            case 'copy':
                $newNode = $this->_backend->copy($_source->statpath, $_destination->statpath);
                break;
            case 'move':
                $newNode = $this->_backend->rename($_source->statpath, $_destination->statpath);
                break;
        }

        return $newNode;
    }
    
    /**
     * copy folder node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @return Tinebase_Model_Tree_Node
     * @throws Filemanager_Exception_NodeExists
     * 
     * @todo add $_forceOverwrite?
     */
    protected function _copyFolderNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination)
    {
        $newNode = $this->_createNode($_destination, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        
        // recursive copy for (sub-)folders/files
        $filter = new Tinebase_Model_Tree_Node_Filter(array(array(
            'field'    => 'path', 
            'operator' => 'equals', 
            'value'    => $_source->flatpath,
        )));
        $result = $this->search($filter);
        if (count($result) > 0) {
            $this->copyNodes($result->path, $newNode->path);
        }
        
        return $newNode;
    }
    
    /**
     * move nodes
     * 
     * @param array $_sourceFilenames array->multiple
     * @param string|array $_destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $_forceOverwrite
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function moveNodes($_sourceFilenames, $_destinationFilenames, $_forceOverwrite = FALSE)
    {
        return $this->_copyOrMoveNodes($_sourceFilenames, $_destinationFilenames, 'move', $_forceOverwrite);
    }
    
    /**
     * move single node
     * 
     * @param Tinebase_Model_Tree_Node_Path $_source
     * @param Tinebase_Model_Tree_Node_Path $_destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     */
    protected function _moveNode(Tinebase_Model_Tree_Node_Path $_source, Tinebase_Model_Tree_Node_Path $_destination, $_forceOverwrite = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Move Node ' . $_source->flatpath . ' to ' . $_destination->flatpath);
        
        $sourceNode = $this->_backend->stat($_source->statpath);
        
        switch ($sourceNode->type) {
            case Tinebase_Model_Tree_FileObject::TYPE_FILE:
                $movedNode = $this->_copyOrMoveFileNode($_source, $_destination, 'move', $_forceOverwrite);
                break;
            case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                $movedNode = $this->_moveFolderNode($_source, $_destination, $_forceOverwrite);
                break;
        }
        
        return $movedNode;
    }
    
    /**
     * move folder node
     * 
     * @param Tinebase_Model_Tree_Node_Path $source
     * @param Tinebase_Model_Tree_Node $sourceNode [unused]
     * @param Tinebase_Model_Tree_Node_Path $destination
     * @param boolean $_forceOverwrite
     * @return Tinebase_Model_Tree_Node
     * @throws Filemanager_Exception_NodeExists
     */
    protected function _moveFolderNode($source, $destination, $_forceOverwrite = FALSE)
    {
        $this->_backend->checkPathACL($source, 'get', FALSE);
        
        $destinationParentPathRecord = $destination->getParent();
        $destinationNodeName = NULL;
        
        $this->_backend->checkPathACL($destinationParentPathRecord, 'update');
        // TODO do we need this if??
        //if ($source->getParent()->flatpath != $destinationParentPathRecord->flatpath) {
            try {
                $this->_checkIfExists($destination);
                // check if there is a node our user can't see
                if (null !== $this->_backend->_getTreeNodeBackend()->getChild($destinationParentPathRecord->getNode(),
                        $destination->name, false, false)) {
                    throw new Tinebase_Exception_SystemGeneric('The destination already exists, but you don\'t have the right to see it.');
                }
            } catch (Filemanager_Exception_NodeExists $fene) {
                if ($_forceOverwrite && $source->statpath !== $destination->statpath) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Removing folder node ' . $destination->statpath);
                    $this->_backend->rmdir($destination->statpath, TRUE);
                } else if (! $_forceOverwrite) {
                    throw $fene;
                }
            }
//        } else {
//            if (! $_forceOverwrite) {
//                $this->_checkIfExists($destination);
//            }
//        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Rename Folder ' . $source->statpath . ' -> ' . $destination->statpath);

        $this->_backend->rename($source->statpath, $destination->statpath);

        $movedNode = $this->_backend->stat($destination->statpath);
        if ($destinationNodeName !== NULL) {
            $movedNode->name = $destinationNodeName;
        }
        
        return $movedNode;
    }

    /**
     * delete nodes
     * 
     * @param array $_filenames string->single file, array->multiple
     * @return int delete count
     * 
     * @todo add recursive param?
     */
    public function deleteNodes($_filenames)
    {
        $deleteCount = 0;
        foreach ($_filenames as $filename) {
            if ($this->_deleteNode($filename)) {
                $deleteCount++;
            }
        }
        
        return $deleteCount;
    }

    /**
     * delete node
     * 
     * @param string $_flatpath
     * @return boolean
     * @throws Tinebase_Exception_NotFound
     */
    protected function _deleteNode($_flatpath)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Delete path: ' . $_flatpath);

        $flatpathWithBasepath = $this->addBasePath($_flatpath);
        list($parentPathRecord, $nodeName) = Tinebase_Model_Tree_Node_Path::getParentAndChild($flatpathWithBasepath);
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($flatpathWithBasepath);
        
        $this->_backend->checkPathACL($parentPathRecord, 'delete');
        $success = $this->_deleteNodeInBackend($pathRecord, $_flatpath);

        return $success;
    }
    
    /**
     * delete node in backend
     * 
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_flatpath
     * @return boolean
     */
    protected function _deleteNodeInBackend(Tinebase_Model_Tree_Node_Path $_path, $_flatpath)
    {
        $success = FALSE;
        
        $node = $this->_backend->stat($_path->statpath);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ' Removing path ' . $_path->flatpath . ' of type ' . $node->type);
        
        switch ($node->type) {
            case Tinebase_Model_Tree_FileObject::TYPE_FILE:
                $success = $this->_backend->unlink($_path->statpath);
                break;
            case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                $success = $this->_backend->rmdir($_path->statpath, TRUE);
                break;
        }
        
        return $success;
    }
    
    /**
     * Deletes a set of records.
     *
     * returns a set of deleted records. If some records could not be deleted, the set of returned records will
     * have been deleted anyway.
     *
     * NOTE: it is not possible to delete folders like this, it would lead to
     * Tinebase_Exception_InvalidArgument: can not unlink directories
     *
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $nodes = $this->getMultiple($_ids);
        /** @var Tinebase_Model_Tree_Node $node */
        foreach ($nodes as $node) {
            if ($this->_backend->checkPathACL(Tinebase_Model_Tree_Node_Path::createFromStatPath(
                    $this->_backend->getPathOfNode($node, true)), 'delete', true, false)) {
                $this->_backend->deleteFileNode($node);
            } else {
                $nodes->removeRecord($node);
            }
        }
        
        return $nodes;
    }

    /**
     * file message and returns parent node
     *
     * @param Felamimail_Model_MessageFileLocation $location
     * @param Felamimail_Model_Message $message
     * @return Filemanager_Model_Node|null
     * @throws Filemanager_Exception_NodeExists
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fileMessage(Felamimail_Model_MessageFileLocation $location, Felamimail_Model_Message $message)
    {
        if ($location->type === Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT) {
            // file message as attachment
            return parent::fileMessage($location, $message);
        }
        $targetPath = $this->_getLocationTargetPath($location);

        $tempFile = Felamimail_Controller_Message::getInstance()->putRawMessageIntoTempfile($message);
        $filename = Felamimail_Controller_Message::getInstance()->getMessageNodeFilename($message);
        $emlNode = $this->_createNodeFromTempfile($targetPath, $filename, $tempFile);

        $emlNode->description = $this->_getMessageNodeDescription($message);
        $emlNode->last_modified_time = Tinebase_DateTime::now();
        $this->update($emlNode);

        $parent = $this->get($emlNode->parent_id);
        Felamimail_Controller_MessageFileLocation::getInstance()->createMessageLocationForRecord($message, $location, $parent, $emlNode);

        return $parent;
    }

    protected function _createNodeFromTempfile($targetPath, $filename, $tempFile)
    {
        try {
            $node = $this->createNodes(
                array($targetPath . '/' . $filename),
                Tinebase_Model_Tree_FileObject::TYPE_FILE,
                array($tempFile->getId()),
                /* $_forceOverwrite */
                false
            )->getFirstRecord();
        } catch (Filemanager_Exception_NodeExists $fene) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' ' . $fene->getMessage());
            return null;
        }

        return $node;
    }

    protected function _getLocationTargetPath($location)
    {
        if (isset($location['record_id']['path'])) {
            $targetPath = $location['record_id']['path'];
        } else {
            if (is_array($location['record_id'])) {
                if (! isset($location['record_id']['id'])) {
                    throw new Tinebase_Exception_InvalidArgument('path or id required in record_id');
                }
                $recordId = $location['record_id']['id'];
            } else {
                $recordId = $location['record_id'];
            }
            $node = $this->get($recordId);
            $targetPath = $node->path;
        }
        return $targetPath;
    }

    public function fileMessageAttachment($location, $message, $attachment)
    {
        if ($location->type === Felamimail_Model_MessageFileLocation::TYPE_ATTACHMENT) {
            return parent::fileMessageAttachment($location, $message, $attachment);
        }

        $tempFile = Felamimail_Controller_Message::getInstance()->putRawMessageIntoTempfile(
            $message,
            $attachment['partId']);

        $filename = $this->_getfiledAttachmentFilename($attachment, $message);
        $targetPath = $this->_getLocationTargetPath($location);
        $node = $this->_createNodeFromTempfile($targetPath, $filename, $tempFile);

        return $node ? $this->get($node->parent_id) : false;
    }

    /**
     * create node description from message data
     *
     * @param Felamimail_Model_Message $message
     * @return string
     *
     * TODO use/create toString method for Felamimail_Model_Message?
     */
    protected function _getMessageNodeDescription(Felamimail_Model_Message $message)
    {
        // switch to user tz
        $message->setTimezone(Tinebase_Core::getUserTimezone());

        $translate = Tinebase_Translation::getTranslation('Felamimail');

        $description = '';
        $fieldsToAddToDescription = array(
            $translate->_('Received') => 'received',
            $translate->_('To') => 'to',
            $translate->_('Cc') => 'cc',
            $translate->_('Bcc') => 'bcc',
            $translate->_('From (E-Mail)') => 'from_email',
            $translate->_('From (Name)') => 'from_name',
            $translate->_('Body') => 'body',
            $translate->_('Attachments') => 'attachments'
        );

        foreach ($fieldsToAddToDescription as $label => $field) {
            $description .= $label . ': ';

            switch ($field) {
                case 'received':
                    $description .= $message->received->toString();
                    break;
                case 'body':
                    $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
                    $plainText = $completeMessage->getPlainTextBody();
                    $description .= $plainText ."\n";
                    break;
                case 'attachments':
                    foreach ((array) $message->{$field} as $attachment) {
                        if (is_array($attachment) && isset($attachment['filename']))
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' ' . print_r($attachment, true));
                        $description .= '  ' . $attachment['filename'] . "\n";
                    }
                    break;
                default:
                    $value = $message->{$field};
                    if (is_array($value)) {
                        $description .= implode(', ', $value);
                    } else {
                        $description .= $value;
                    }
            }
            $description .= "\n";
        }

        return Tinebase_Core::filterInputForDatabase($description);
    }

    /**
     * @param Tinebase_Model_ModificationLog $modification
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $modification)
    {
        Tinebase_Tree::getInstance()->applyReplicationModificationLog($modification);
    }

    /**
     * @param Tinebase_Model_ModificationLog $_modification
     * @param bool $_dryRun
     */
    public function undoReplicationModificationLog(Tinebase_Model_ModificationLog $_modification, $_dryRun)
    {
        Tinebase_Tree::getInstance()->undoReplicationModificationLog($_modification, $_dryRun);
    }

    /**
     * Return usage array of a folder
     *
     * @param $_id
     * @return array of folder usage
     */
    public function getFolderUsage($_id)
    {
        $childIds = $this->_backend->getAllChildIds($_id, array(), false);

        $createdBy = array();;
        $type = array();
        foreach($childIds as $id) {
            try {
                $fileNode = $this->_backend->get($id);
            } catch(Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $fileNode->type) {
                continue;
            }

            if (!isset($createdBy[$fileNode->created_by])) {
                $createdBy[$fileNode->created_by] = array(
                    'size'          => $fileNode->size,
                    'revision_size' => $fileNode->revision_size
                );
            } else {
                $createdBy[$fileNode->created_by]['size']           += $fileNode->size;
                $createdBy[$fileNode->created_by]['revision_size']  += $fileNode->revision_size;
            }

            $ext = pathinfo($fileNode->name, PATHINFO_EXTENSION);

            if (!isset($type[$ext])) {
                $type[$ext] = array(
                    'size'          => $fileNode->size,
                    'revision_size' => $fileNode->revision_size
                );
            } else {
                $type[$ext]['size']           += $fileNode->size;
                $type[$ext]['revision_size']  += $fileNode->revision_size;
            }
        }

        return array('createdBy' => $createdBy, 'type' => $type);
    }

    /**
     * creates a node from a tempfile with download link in a defined folder
     *
     * - create folder path in Filemanager if it does not exist
     * - create new file node from temp file
     * - create download link for temp file
     *
     * @param Tinebase_Model_TempFile $tempFile
     * @param string $_path
     * @param string $_password
     * @return Filemanager_Model_DownloadLink
     */
    public function createNodeWithDownloadLinkFromTempFile(Tinebase_Model_TempFile $_tempFile, $_path, $_password = '')
    {
        // check if path exists, if not: create
        $folderPathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($_path));
        if (! $this->_backend->fileExists($folderPathRecord->statpath)) {
            $this->_createNode($folderPathRecord, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        }

        $filePathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($this->addBasePath($_path . '/' . $_tempFile->name));
        $filenode = $this->_createNode(
            $filePathRecord,
            Tinebase_Model_Tree_FileObject::TYPE_FILE,
            $_tempFile->getId(),
        // TODO always overwrite?
            /* $_forceOverwrite */ true
        );

        $downloadLink = Filemanager_Controller_DownloadLink::getInstance()->create(new Filemanager_Model_DownloadLink(array(
            'node_id'       => $filenode->getId(),
            'expiry_date'   => Tinebase_DateTime::now()->addDay(30)->toString(),
            'password'      => $_password
        )));

        return $downloadLink;
    }
}
