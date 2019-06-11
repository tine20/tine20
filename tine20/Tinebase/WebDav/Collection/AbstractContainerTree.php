<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle top level folders for an application
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Collection_AbstractContainerTree
    extends \Sabre\DAV\Collection
    implements \Sabre\DAV\IProperties, \Sabre\DAVACL\IACL, \Sabre\DAV\IExtendedCollection
{
    /**
     * the current application object
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName;

    /**
     * container model name
     *
     * one of: Tinebase_Model_Container | Tinebase_Model_Tree_Node
     *
     * @var string
     */
    protected $_containerModel = 'Tinebase_Model_Container';

    /**
     * container controller
     *
     * @var Tinebase_Application_Container_Interface
     */
    protected $_containerController = null;

    /**
     * app has personal folders
     *
     * @var string
     */
    protected $_hasPersonalFolders = true;
    
    /**
     * app has records folder
     *
     * @var string
     */
    protected $_hasRecordFolder = true;

    /**
     * app can support delegations
     *
     * @var boolean
     */
    protected $_canSupportDelegations = true;
    
    /**
     * the current path
     * 
     * @var string
     */
    protected $_path;
    
    /**
     * @var array
     */
    protected $_pathParts;
    
    /**
     * @var boolean
     */
    protected $_useIdAsName = false;

    /**
     * @var array
     */
    protected $_instanceCache = array();

    /**
     * @var array
     */
    protected static $_classCache = array (
        '_getUser' => array()
    );

    /**
     * contructor
     * 
     * @param string $path          the current path
     * @param array  $options       options
     */
    public function __construct($path, $options = array())
    {
        $this->_path        = $path;

        // handle legacy (throw WARN/NOTICE in log?)
        if ($options === true) {
            $options = array(
                'useIdAsName' => true
            );
        }

        if (isset($options['useIdAsName'])) {
            $this->_useIdAsName = $options['useIdAsName'];
        }
        if (isset($options['containerModel'])) {
            $this->_containerModel = $options['containerModel'];
        }

        switch ($this->_containerModel) {
            case 'Tinebase_Model_Container':
                $this->_containerController = Tinebase_Container::getInstance();
                break;
            case 'Tinebase_Model_Tree_Node':
                $this->_containerController = Tinebase_FileSystem::getInstance();
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('invalid container model given');
        }
    }

    public static function clearClassCache()
    {
        foreach (static::$_classCache as &$val) {
            $val = array();
        }
    }

    /**
     * use login as folder name
     *
     * @return boolean
     */
    protected function _useLoginAsFolderName()
    {
        return Tinebase_Config::getInstance()->get(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\Collection::createDirectory()
     */
    public function createDirectory($name) 
    {
        return $this->_createContainer(array(
            'name' => $name
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\IExtendedCollection::createExtendedCollection()
     */
    public function createExtendedCollection($name, array $resourceType, array $properties)
    {
        return $this->_createContainer(array(
            'name'  => isset($properties['{DAV:}displayname']) ? $properties['{DAV:}displayname'] : $name,
            'uuid'  => $name,
            'color' => isset($properties['{http://apple.com/ns/ical/}calendar-color']) ? substr($properties['{http://apple.com/ns/ical/}calendar-color'], 0, 7) : null
        ));
    }
    
    /**
     * @param string $name
     * @return Tinebase_WebDav_Container_Abstract|Tinebase_WebDav_Collection_AbstractContainerTree|Tinebase_Frontend_WebDAV_RecordCollection
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        if (isset($this->_instanceCache[__FUNCTION__][$name])) {
            return $this->_instanceCache[__FUNCTION__][$name];
        }

        switch (count($this->_getPathParts())) {
            # path == /<applicationPrefix> (for example calendars)
            # return folders for currentuser, other users and 'shared' folder
            # $name can be
            # * contact_id of user
            # * 'shared'
            case 1:
                $child = $this->_getToplevelTree($name);
                break;

            # path == /<applicationPrefix>/<contactid>|'shared'
            # list container
            case 2:
                $child = $this->_getContainerTree($name);
                break;

            default:
                throw new Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        $this->_instanceCache[__FUNCTION__][$name] = $child;

        return $child;
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _getToplevelTree($name)
    {
        if ($name === Tinebase_Model_Container::TYPE_SHARED ||
            ($this->_hasRecordFolder && $name === Tinebase_FileSystem::FOLDER_TYPE_RECORDS)) {
            $path = $this->_path . '/' . $name;

        } elseif ($this->_hasPersonalFolders) {
            if ($name === '__currentuser__') {
                $path = $this->_path . '/__currentuser__';

            } else {
                try {
                    // check if it exists only
                    $this->_getUser($name);

                } catch (Tinebase_Exception_NotFound $tenf) {
                    $message = "Directory $this->_path/$name not found";
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . ' ' . $message);
                    throw new \Sabre\DAV\Exception\NotFound($message);
                }

                $path = $this->_path . '/' . $name;
            }

        } else {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        $className = $this->_getApplicationName() . '_Frontend_WebDAV';
        return new $className($path, array(
            'useIdAsName'       => $this->_useIdAsName,
            'containerModel'    => $this->_containerModel,
        ));
    }

    /**
     * @param $name
     * @return Tinebase_Frontend_WebDAV_RecordCollection
     * @throws Tinebase_Exception_Duplicate
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _getContainerTree($name)
    {
        if (Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED) {
            $directory = $this->_getSharedDirectory($name);

        } elseif ($this->_hasRecordFolder && Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
            return new Tinebase_Frontend_WebDAV_RecordCollection($this->_path . '/' . $name);

        } elseif ($this->_hasPersonalFolders) {
            $directory = $this->_getPersonalDirectory($name);

        } else {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        if ((! Tinebase_Core::getUser()->hasGrant($directory, Tinebase_Model_Grants::GRANT_READ) ||
                ! Tinebase_Core::getUser()->hasGrant($directory, Tinebase_Model_Grants::GRANT_SYNC))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' User ' . Tinebase_Core::getUser()->getId()
                . ' has either not READ or SYNC grants for container ' . $directory->getId());
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        if ($directory->has('application_id')) {
            $containerApp = Tinebase_Application::getInstance()->getApplicationById($directory->application_id)->name;
        } else {
            $containerApp = $this->_getApplicationName();
        }

        $objectClass = $containerApp . '_Frontend_WebDAV_Container';

        if (! class_exists($objectClass)) {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        return new $objectClass($directory, $this->_useIdAsName);
    }

    protected function _getSharedDirectory($name)
    {
        try {
            if ($this->_containerModel === 'Tinebase_Model_Container') {
                $directory = $this->_getSharedContainer($name);
            } else if ($this->_containerModel === 'Tinebase_Model_Tree_Node') {
                $directory = $this->_getSharedNode($name);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");

        } catch (Tinebase_Exception_InvalidArgument $teia) {
            // invalid container id provided
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        return $directory;
    }

    protected function _getSharedContainer($name)
    {
        if ($name instanceof Tinebase_Model_Container) {
            return $name;
        }

        if (isset(static::$_classCache[__FUNCTION__][$name])) {
            return static::$_classCache[__FUNCTION__][$name];
        }

        if ($this->_useIdAsName) {
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' First try to fetch container by uuid ..');
                $container = Tinebase_Container::getInstance()->getByProperty((string) $name, 'uuid');
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' If that fails by id ...');

                $container = Tinebase_Container::getInstance()->getContainerById($name);
            }
        } else {
            $container = Tinebase_Container::getInstance()->getContainerByName(
                $this->_getApplicationName(),
                (string) $name,
                Tinebase_Model_Container::TYPE_SHARED
            );
        }

        static::$_classCache[__FUNCTION__][$name] = $container;

        return $container;
    }

    /**
     * get shared node
     *
     * @param $name
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getSharedNode($name)
    {
        return $this->_getNode($name, Tinebase_FileSystem::FOLDER_TYPE_SHARED);
    }

    /**
     * @param $name
     * @return Tinebase_Model_Container|Tinebase_Record_Interface
     * @throws Tinebase_Exception_Duplicate
     * @throws \Sabre\DAV\Exception\NotFound
     */
    protected function _getPersonalDirectory($name)
    {
        if (Tinebase_Helper::array_value(1, $this->_getPathParts()) === '__currentuser__') {
            $accountId = Tinebase_Core::getUser()->accountId;

        } else {
            try {
                $accountId = $this->_getUser(Tinebase_Helper::array_value(1, $this->_getPathParts()))->accountId;

            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
            }
        }

        try {
            if ($this->_containerModel === 'Tinebase_Model_Container') {
                $directory = $this->_getPersonalContainer($name, $accountId);
            } else if ($this->_containerModel === 'Tinebase_Model_Tree_Node') {
                $directory = $this->_getPersonalNode($name, $accountId);
            }

        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");

        } catch (Tinebase_Exception_InvalidArgument $teia) {
            // invalid container id provided
            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
        }

        return $directory;
    }

    protected function _getPersonalContainer($name, $accountId)
    {
        if ($name instanceof Tinebase_Model_Container) {
            return $name;
        }

        $cacheKey = $name . ($this->_useIdAsName ? '' : $accountId);
        if (isset(static::$_classCache[__FUNCTION__][$cacheKey])) {
            return static::$_classCache[__FUNCTION__][$cacheKey];
        }

        if ($this->_useIdAsName) {
            // first try to fetch by uuid ...
            try {
                $container = Tinebase_Container::getInstance()->getByProperty((string) $name, 'uuid');
            } catch (Tinebase_Exception_NotFound $tenf) {
                // ... if that fails by id
                $container = Tinebase_Container::getInstance()->getContainerById($name);
            }

        } else {
            $container = Tinebase_Container::getInstance()->getContainerByName(
                $this->_getApplicationName(),
                (string) $name,
                Tinebase_Model_Container::TYPE_PERSONAL,
                $accountId
            );
        }

        static::$_classCache[__FUNCTION__][$cacheKey] = $container;

        return $container;
    }

    /**
     * @param $name
     * @param $accountId
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getPersonalNode($name, $accountId)
    {
        return $this->_getNode($name, Tinebase_FileSystem::FOLDER_TYPE_PERSONAL);
    }

    protected function _getNode($name, $type)
    {
        $path = $this->_getTreeNodePath($type);
        $statpath = $path->statpath;
        $statpath .= '/' . $name;
        $node = Tinebase_FileSystem::getInstance()->stat($statpath);
        if (Tinebase_Core::getUser()->hasGrant($node,Tinebase_Model_Grants::GRANT_READ)) {
            return $node;
        } else {
            // TODO throw 403?
            throw new Tinebase_Exception_NotFound('no access for node');
        }
    }

    /**
     * get tree node path for this collection
     *
     * @param $containerType
     * @return Tinebase_Model_Tree_Node_Path
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getTreeNodePath($containerType)
    {
        // remove app from path parts
        $pathParts = $this->_pathParts;
        $pathPartsWithoutApp = array_splice($pathParts, 1);
        if (in_array($pathPartsWithoutApp[0], array(
                // NOTE: personal should never be send by an WebDAV client...
                Tinebase_FileSystem::FOLDER_TYPE_PERSONAL, Tinebase_FileSystem::FOLDER_TYPE_SHARED
            )))
        {
            $pathPartsWithoutApp = array_splice($pathPartsWithoutApp, 1);
        }
        $path = Tinebase_Model_Tree_Node_Path::createFromPath(
            Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                $this->_getApplication(),
                $containerType
            ) . '/' . implode('/', $pathPartsWithoutApp));

        return $path;
    }

    /**
     * Returns an array with all the child nodes
     * 
     * the records subtree is not returned as child here. It's only available via getChild().
     *
     * @return \Sabre\DAV\INode[]
     */
    public function getChildren()
    {
        if (isset($this->_instanceCache[__FUNCTION__])) {
            return $this->_instanceCache[__FUNCTION__];
        }

        switch (count($this->_getPathParts())) {
            # path == /<applicationPrefix> (for example calendars)
            # return folders for currentuser, other users and 'shared' folder
            case 1:
                $children = $this->_getPersonalChildren();
                break;

            # path == /<applicationPrefix>/<contactid>|'shared'
            # list container
            case 2:
                $children = $this->_getSharedChildren();
                break;

            default:
                throw new Sabre\DAV\Exception\NotFound("Path $this->_path not found");
                break;
        }

        $this->_instanceCache[__FUNCTION__] = $children;
        return $children;
    }

    protected function _getPersonalChildren()
    {
        $children = array();
        $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);

        if ($this->_hasPersonalFolders) {
            $children[] = $this->getChild(
                $this->_useIdAsName ?
                    Tinebase_Core::getUser()->contact_id :
                    ($this->_useLoginAsFolderName() ?
                        Tinebase_Core::getUser()->accountLoginName :
                        Tinebase_Core::getUser()->accountDisplayName)
            );

            $children = array_merge($children, $this->_getOtherUsersChildren());
        }

        return $children;
    }

    protected function _getOtherUsersChildren()
    {
        $children = array();
        // TODO allow NODES here
        $otherUsers = Tinebase_Container::getInstance()->getOtherUsers(Tinebase_Core::getUser(), $this->_getApplicationName(), array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_SYNC
        ));

        foreach ($otherUsers as $user) {
            if ($user->contact_id && $user->visibility === Tinebase_Model_User::VISIBILITY_DISPLAYED) {
                try {
                    $folderId = $this->_useIdAsName ?
                        $user->contact_id :
                        ($this->_useLoginAsFolderName() ? $user->accountLoginName : $user->accountDisplayName);

                    $children[] = $this->getChild($folderId);
                } catch (\Sabre\DAV\Exception\NotFound $sdavenf) {
                    // ignore contacts not found
                }
            }
        }

        return $children;
    }

    protected function _getSharedChildren()
    {
        $children = array();
        if (Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED) {
            $containers = $this->_getSharedDirectories();

        } elseif ($this->_hasPersonalFolders) {
            if (Tinebase_Helper::array_value(1, $this->_getPathParts()) === '__currentuser__') {
                $accountId = Tinebase_Core::getUser()->accountId;

            } else {
                try {
                    $accountId = $this->_getUser(Tinebase_Helper::array_value(1, $this->_getPathParts()))->accountId;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    throw new \Sabre\DAV\Exception\NotFound("Path $this->_path not found");
                }
            }

            try {
                if ($this->_getApplicationName() === 'Filemanager' || $this->_clientSupportsDelegations()) {
                    $containers = $this->_containerController->getPersonalContainer(
                        Tinebase_Core::getUser(),
                        $this->_getApplicationName(),
                        $accountId,
                        array(
                            Tinebase_Model_Grants::GRANT_READ,
                            Tinebase_Model_Grants::GRANT_SYNC
                        )
                    );
                } else {
                    // NOTE: seems to be the expected behavior for non-delegation clients
                    $containers = $this->_containerController->getContainerByACL(Tinebase_Core::getUser(), $this->_getApplicationName(),  array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_SYNC
                    ));
                }
            } catch (Tinebase_Exception_AccessDenied $tead) {
                throw new Sabre\DAV\Exception\NotFound("Could not find path (" . $tead->getMessage() . ")");
            }

        } else {
            throw new Sabre\DAV\Exception\NotFound("Path $this->_path not found");
        }

        foreach ($containers as $container) {
            try {
                $children[] = $this->getChild($this->_useIdAsName ? $container->getId() : $container->name);
            } catch (\Sabre\DAV\Exception\NotFound $sdavenf) {
                // ignore containers not found
            }
        }

        return $children;
    }
    /**
     * @return Tinebase_Record_RecordSet
     */
    protected function _getSharedDirectories()
    {
        $containers = $this->_containerController->getSharedContainer(
            Tinebase_Core::getUser(),
            $this->_getApplicationName(),
            array(
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_SYNC
            )
        );

        return $containers;
    }
    /**
     * checks if client supports delegations
     *
     * @return boolean
     *
     * @todo don't use $_SERVER to fetch user agent
     * @todo move user agent parsing to Tinebase
     */
    protected function _clientSupportsDelegations()
    {
        if (!$this->_canSupportDelegations) {
            return false;
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
            $clientSupportsDelegations = in_array($backend, array(
                Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX,
                Calendar_Convert_Event_VCalendar_Factory::CLIENT_DAVDROID,
            ));
        } else {
            $clientSupportsDelegations = false;
        }

        return $clientSupportsDelegations;
    }
    
    /**
     * return etag
     * 
     * @return string
     */
    public function getETag()
    {
        $etags = array();
        
        foreach ($this->getChildren() as $child) {
            $etags[] = $child->getETag();
        }
        
        return '"' . sha1(implode(null, $etags)) . '"';
    }
    
    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup()
    {
        return null;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\Node::getLastModified()
     */
    public function getLastModified()
    {
        $lastModified = 1;
        
        foreach ($this->getChildren() as $child) {
            $lastModified = $child->getLastModified() > $lastModified ? $child->getLastModified() : $lastModified;
        }
        
        return $lastModified;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *      
     * @todo implement real logic
     * @return array
     */
    public function getACL() 
    {
        $principal = 'principals/users/' . Tinebase_Core::getUser()->contact_id;
        
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $principal,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $principal,
                'protected' => true,
            )
        );
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        if (count($this->_getPathParts()) === 2 && 
            Tinebase_Helper::array_value(1, $this->_getPathParts()) !== Tinebase_Model_Container::TYPE_SHARED &&
            !$this->_useIdAsName
        ) {
            try {
                $user = $this->_getUser(Tinebase_Helper::array_value(1, $this->_getPathParts()));
                
                $name = $this->_useLoginAsFolderName() ? $user->accountLoginName : $user->accountDisplayName;
                
            } catch (Tinebase_Exception_NotFound $tenf) {
                list(,$name) = Sabre\DAV\URLUtil::splitPath($this->_path);
            }
            
        } else {
            list(,$name) = Sabre\DAV\URLUtil::splitPath($this->_path);
        }
        
        return $name;
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @return string|null
     */
    public function getOwner()
    {
        if (count($this->_getPathParts()) === 2 && $this->getName() !== Tinebase_Model_Container::TYPE_SHARED) {
            try {
                $user = $this->_getUser(Tinebase_Helper::array_value(1, $this->_getPathParts()));
            } catch (Tinebase_Exception_NotFound $tenf) {
                return null;
            }
            
            return 'principals/users/' . $user->contact_id;
        }
        
        return null;
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . ' ' . print_r($requestedProperties, true));
        
        $response = array();
        $pathParts = $this->_getPathParts();
    
        foreach ($requestedProperties as $property) {
            switch ($property) {
                // owncloud specific
                // owncloud does send paths starting with /webdav ... some comments here say that would be not ok?
                case '{http://owncloud.org/ns}size':
                    if (Tinebase_Model_Tree_Node::class === $this->_containerModel) {
                        /*if (count($pathParts) === 1) {
                            // webdav -> root file system ... what to return? all? all visible? complicated...
                            // and why return anything? it will not be displayed anyway?
                        } else*/if (count($pathParts) === 2) {
                            $size = 0;
                            foreach ($this->getChildren() as $node) {
                                $size += $node->getSize();
                            }
                            $response[$property] = $size;
                        }
                    }
                    break;

                case '{DAV:}quota-available-bytes':
                    if (Tinebase_Model_Tree_Node::class === $this->_containerModel) {
                        if (count($pathParts) === 1) {
                            // webdav -> root file system ...
                            if (0 === Tinebase_FileSystem_Quota::getRootQuotaBytes() &&
                                    0 === Tinebase_FileSystem_Quota::getPersonalQuotaBytes()) {
                                // unlimited: RFC 4331: If a resource has no quota enforced or unlimited storage
                                // ("infinite limits"), the server MAY choose not to return this property
                                break;
                            }
                            $response[$property] = Tinebase_FileSystem_Quota::getPersonalQuotaBytes() ?:
                                Tinebase_FileSystem_Quota::getRootFreeBytes();
                        }
                    }
                    break;

                case '{DAV:}quota-used-bytes':
                    if (Tinebase_Model_Tree_Node::class === $this->_containerModel) {
                        if (count($pathParts) === 1) {
                            // webdav -> root file system ...
                            if (0 === Tinebase_FileSystem_Quota::getRootQuotaBytes()) {
                                // owncloud displays garbage if we dont have a quota-available-bytes value
                                // so better not return something here
                                break;
                            }
                            $response[$property] = Tinebase_FileSystem_Quota::getRootUsedBytes();
                        }
                    }
                    break;

                case '{DAV:}displayname':
                    if (count($this->_getPathParts()) === 2 && $this->getName() !== Tinebase_Model_Container::TYPE_SHARED) {
                        try {
                            $user = $this->_getUser(Tinebase_Helper::array_value(1, $this->_getPathParts()));
                            $contact = Addressbook_Controller_Contact::getInstance()->get($user->contact_id);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            continue 2;
                        }
                        
                        $response[$property] = $contact->n_fileas;
                    }
                    
                    break;
                    
                case '{DAV:}owner':
                    if ($this->getOwner()) {
                        $response[$property] = new \Sabre\DAVACL\Property\Principal(
                            \Sabre\DAVACL\Property\Principal::HREF, $this->getOwner()
                        );
                    }
                    
                    break;
                    
                case '{DAV:}getetag':
                    $response[$property] = $this->getETag();
                    
                    break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . ' ' . print_r($response, true));
        
        return $response;
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existant property is always succesful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname. 
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param array $mutations 
     * @return bool|array 
     */
    public function updateProperties($mutations) 
    {
        $result = array(
            200 => array(),
            403 => array()
        );

        foreach ($mutations as $key => $value) {
            switch ($key) {
                // once iCal tried to set default-alarm config with a negative feedback
                // it doesn't send default-alarms to the server any longer. So we fake
                // success here as workaround to let the client send its default alarms
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-datetime':
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-date':
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-datetime':
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-date':
                    // fake success
                    $result['200'][$key] = null;
                    break;

                default:
                    $result['403'][$key] = null;
            }
        }

        return $result;
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
    
    /**
     * return application object
     * 
     * @return Tinebase_Model_Application
     */
    protected function _getApplication()
    {
        if (!$this->_application) {
            $this->_application = Tinebase_Application::getInstance()->getApplicationByName($this->_getApplicationName());
        }
        
        return $this->_application;
    }
    
    /**
     * creates a new container
     * 
     * @todo allow to create personal folders only when in currents users own path
     * TODO split function (node + controller)
     *
     * @param  array  $properties  properties for new container
     * @throws \Sabre\DAV\Exception\Forbidden
     * @return Tinebase_Model_Container
     */
    protected function _createContainer(array $properties) 
    {
        $permissionDeniedMessage = 'Permission denied to create directory ' . $properties['name'];
        if (count($this->_getPathParts()) !== 2) {
            throw new \Sabre\DAV\Exception\Forbidden($permissionDeniedMessage);
        }
        
        $containerType = Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED ?
            Tinebase_Model_Container::TYPE_SHARED :
            Tinebase_Model_Container::TYPE_PERSONAL;

        try {
            if ($this->_containerModel === 'Tinebase_Model_Container') {
                $newContainer = new Tinebase_Model_Container(array_merge($properties, array(
                    'type' => $containerType,
                    'backend' => 'Sql',
                    'application_id' => $this->_getApplication()->getId(),
                    'model' => Tinebase_Core::getApplicationInstance($this->_getApplicationName())->getDefaultModel()
                )));
                $container = Tinebase_Container::getInstance()->addContainer($newContainer);

            } else if ($this->_containerModel === 'Tinebase_Model_Tree_Node') {
                $path = $this->_getTreeNodePath($containerType);
                Tinebase_FileSystem::getInstance()->checkPathACL($path, 'add');
                $statpath = $path->statpath . '/' . $properties['name'];
                if ($path->isToplevelPath()) {
                    $container = Tinebase_FileSystem::getInstance()->createAclNode($statpath);
                } else {
                    $container = Tinebase_FileSystem::getInstance()->mkdir($statpath);
                    Tinebase_FileSystem::getInstance()->setAclFromParent($statpath);
                }

            } else {
                throw Tinebase_Exception_AccessDenied('wrong model');
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new \Sabre\DAV\Exception\Forbidden($permissionDeniedMessage);
        }

        return $container;
    }
    
    /**
     * return application name
     * 
     * @return string
     */
    protected function _getApplicationName()
    {
        if (! $this->_applicationName) {
            $this->_applicationName = Tinebase_Helper::array_value(0, explode('_', get_class($this)));
        }
        
        return $this->_applicationName;
    }
    
    /**
     * get path parts
     * 
     * @return array
     */
    protected function _getPathParts()
    {
        if (!$this->_pathParts) {
            $this->_pathParts = $this->_parsePath($this->_path);
        }
        
        return $this->_pathParts;
    }
    
    /**
     * split path into parts
     * 
     * @param  string  $_path
     * @return array
     */
    protected function _parsePath($_path)
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        return $pathParts;
    }

    protected function _getUser($_id)
    {
        $classCacheId = ($this->_useIdAsName ? 'contact_id' : ($this->_useLoginAsFolderName() ? 'accountLoginName' : 'accountDisplayName')) . $_id;

        if (isset(self::$_classCache[__FUNCTION__][$classCacheId])) {
            return self::$_classCache[__FUNCTION__][$classCacheId];
        }

        if ($this->_useIdAsName) {
            $contact = Addressbook_Controller_Contact::getInstance()->get($_id);
            $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $contact->account_id, 'Tinebase_Model_FullUser');
        } else {
            if ($this->_useLoginAsFolderName()) {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $_id, 'Tinebase_Model_FullUser');
            } else {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountDisplayName', $_id, 'Tinebase_Model_FullUser');
            }
        }

        self::$_classCache[__FUNCTION__][$classCacheId] = $user;

        return $user;
    }

    public function clearInstanceCache()
    {
        $this->_instanceCache = [];
    }
}
