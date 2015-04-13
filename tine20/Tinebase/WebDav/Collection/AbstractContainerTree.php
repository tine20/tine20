<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle top level folders for an application
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Collection_AbstractContainerTree extends \Sabre\DAV\Collection implements \Sabre\DAV\IProperties, \Sabre\DAVACL\IACL, \Sabre\DAV\IExtendedCollection
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
     * 
     * @var boolean
     */
    protected $_useIdAsName;
    
    protected static $_classCache = array (
        '_getContact' => array()
    );
    
    /**
     * contructor
     * 
     * @param string $path         the current path
     * @param bool   $useIdAsName  use name or id as node name
     */
    public function __construct($path, $useIdAsName = false)
    {
        $this->_path        = $path;
        $this->_useIdAsName = $useIdAsName;
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
    function createExtendedCollection($name, array $resourceType, array $properties)
    {
        return $this->_createContainer(array(
            'name'  => isset($properties['{DAV:}displayname']) ? $properties['{DAV:}displayname'] : $name,
            'uuid'  => $name,
            'color' => isset($properties['{http://apple.com/ns/ical/}calendar-color']) ? substr($properties['{http://apple.com/ns/ical/}calendar-color'], 0, 7) : null
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        switch (count($this->_getPathParts())) {
            # path == /<applicationPrefix> (for example calendars)
            # return folders for currentuser, other users and 'shared' folder
            # $name can be
            # * contact_id of user
            # * 'shared'
            case 1:
                if ($name === Tinebase_Model_Container::TYPE_SHARED ||
                    ($this->_hasRecordFolder && $name === Tinebase_FileSystem::FOLDER_TYPE_RECORDS)) {
                    $path = $this->_path . '/' . $name;
                    
                } elseif ($this->_hasPersonalFolders) {
                    if ($name === '__currentuser__') {
                        $path = $this->_path . '/__currentuser__';
                        
                    } else {
                        try {
                            $contact = $this->_getContact($name);
                            
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                        }
                        
                        $path = $this->_path . '/' . ($this->_useIdAsName ? $contact->getId() : $contact->n_fileas);
                    }
                    
                } else {
                    throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                }
                
                $className = $this->_getApplicationName() . '_Frontend_WebDAV';
                
                return new $className($path, $this->_useIdAsName);
                
                break;
                
            # path == /<applicationPrefix>/<contactid>|'shared'
            # list container
            case 2:
                if (Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED) {
                    try { 
                        if ($name instanceof Tinebase_Model_Container) {
                            $container = $name;
                        } elseif ($this->_useIdAsName) {
                            // first try to fetch by uuid ...
                            try {
                                $container = Tinebase_Container::getInstance()->getByProperty($name, 'uuid');
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                // ... if that fails by id
                                $container = Tinebase_Container::getInstance()->getContainerById($name);
                            }
                        } else {
                            $container = Tinebase_Container::getInstance()->getContainerByName(
                                $this->_getApplicationName(), 
                                $name, 
                                Tinebase_Model_Container::TYPE_SHARED
                            );
                        }
                        
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                        
                    } catch (Tinebase_Exception_InvalidArgument $teia) {
                        // invalid container id provided
                        throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                    }
                } elseif ($this->_hasRecordFolder && Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
                    
                    return new Tinebase_Frontend_WebDAV_RecordCollection($this->_path . '/' . $name);
                    
                } elseif ($this->_hasPersonalFolders) {
                    if (Tinebase_Helper::array_value(1, $this->_getPathParts()) === '__currentuser__') {
                        $accountId = Tinebase_Core::getUser()->accountId;
                        
                    } else {
                        try {
                            $accountId = $this->_getContact(Tinebase_Helper::array_value(1, $this->_getPathParts()))->account_id;
                            
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                        }
                    }
                    
                    try {
                        if ($name instanceof Tinebase_Model_Container) {
                            $container = $name;
                        } elseif ($this->_useIdAsName) {
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
                                $name,
                                Tinebase_Model_Container::TYPE_PERSONAL, 
                                $accountId
                            );
                        }
                        
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                        
                    } catch (Tinebase_Exception_InvalidArgument $teia) {
                        // invalid container id provided
                        throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                    }
                    
                } else {
                    throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                }
                
                if (!Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_READ) ||
                    !Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_SYNC)) {
                    throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                }
                
                $objectClass = Tinebase_Application::getInstance()->getApplicationById($container->application_id)->name . '_Frontend_WebDAV_Container';
                
                if (! class_exists($objectClass)) {
                    throw new \Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                }
                 
                return new $objectClass($container, $this->_useIdAsName);
                
                break;
                
            default:
                throw new Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
            
                break;
        }
    }
    
    /**
     * Returns an array with all the child nodes
     * 
     * the records subtree is not returned as child here. It's only available via getChild().
     *
     * @return \Sabre\DAV\INode[]
     */
    function getChildren()
    {
        $children = array();
        
        switch (count($this->_getPathParts())) {
            # path == /<applicationPrefix> (for example calendars)
            # return folders for currentuser, other users and 'shared' folder
            case 1:
                $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);
                
                if ($this->_hasPersonalFolders) {
                    $children[] = $this->getChild($this->_useIdAsName ? Tinebase_Core::getUser()->contact_id : Tinebase_Core::getUser()->accountDisplayName);
                    
                    $otherUsers = Tinebase_Container::getInstance()->getOtherUsers(Tinebase_Core::getUser(), $this->_getApplicationName(), array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_SYNC
                    ));
                    
                    foreach ($otherUsers as $user) {
                        if ($user->contact_id && $user->visibility === Tinebase_Model_User::VISIBILITY_DISPLAYED) {
                            try {
                                $children[] = $this->getChild($this->_useIdAsName ? $user->contact_id : $user->accountDisplayName);
                            } catch (\Sabre\DAV\Exception\NotFound $sdavenf) {
                                // ignore contacts not found
                            }
                        }
                    }
                }
        
                break;
            
            # path == /<applicationPrefix>/<contactid>|'shared'
            # list container
            case 2:
                if (Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED) {
                    $containers = Tinebase_Container::getInstance()->getSharedContainer(
                        Tinebase_Core::getUser(),
                        $this->_getApplicationName(),
                        array(
                            Tinebase_Model_Grants::GRANT_READ,
                            Tinebase_Model_Grants::GRANT_SYNC
                        )
                    );
                    
                } elseif ($this->_hasPersonalFolders) {
                    if (Tinebase_Helper::array_value(1, $this->_getPathParts()) === '__currentuser__') {
                        $accountId = Tinebase_Core::getUser()->accountId;
                        
                    } else {
                        try {
                            $accountId = $this->_getContact(Tinebase_Helper::array_value(1, $this->_getPathParts()))->account_id;
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            throw new \Sabre\DAV\Exception\NotFound("Path $this->_path not found");
                        }
                    }
                    
                    try {
                        if ($this->_getApplicationName() !== 'Calendar' || $this->_clientSupportsDelegations()) {
                            $containers = Tinebase_Container::getInstance()->getPersonalContainer(
                                Tinebase_Core::getUser(),
                                $this->_getApplicationName(),
                                $accountId,
                                array(
                                    Tinebase_Model_Grants::GRANT_READ, 
                                    Tinebase_Model_Grants::GRANT_SYNC
                                )
                            ); 
                        } else {
                            $containers = Tinebase_Container::getInstance()->getContainerByACL(Tinebase_Core::getUser(), $this->_getApplicationName(),  array(
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
                        $children[] = $this->getChild($container);
                    } catch (\Sabre\DAV\Exception\NotFound $sdavenf) {
                        // ignore containers not found
                    }
                }
                
                break;
                
            default:
                throw new Sabre\DAV\Exception\NotFound("Path $this->_path not found");
                
                break;
        }
        
        return $children;
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
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
            $clientSupportsDelegations = ($backend === Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX);
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
                $contact = $this->_getContact(Tinebase_Helper::array_value(1, $this->_getPathParts()));
                
                $name = $contact->n_fileas;
                
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
                $contact = $this->_getContact(Tinebase_Helper::array_value(1, $this->_getPathParts()));
            } catch (Tinebase_Exception_NotFound $tenf) {
                return null;
            }
            
            return 'principals/users/' . $contact->getId();
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
    
        foreach ($requestedProperties as $property) {
            switch ($property) {
                case '{DAV:}displayname':
                    if (count($this->_getPathParts()) === 2 && $this->getName() !== Tinebase_Model_Container::TYPE_SHARED) {
                        try {
                            $contact = $this->_getContact(Tinebase_Helper::array_value(1, $this->_getPathParts()));
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            continue;
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
     * 
     * @param  array  $properties  properties for new container
     * @throws \Sabre\DAV\Exception\Forbidden
     * @return Tinebase_Model_Container
     */
    protected function _createContainer(array $properties) 
    {
        if (count($this->_getPathParts()) !== 2) {
            throw new \Sabre\DAV\Exception\Forbidden('Permission denied to create directory ' . $properties['name']);
        }
        
        $containerType = Tinebase_Helper::array_value(1, $this->_getPathParts()) == Tinebase_Model_Container::TYPE_SHARED ?
            Tinebase_Model_Container::TYPE_SHARED :
            Tinebase_Model_Container::TYPE_PERSONAL;
        
        $newContainer = new Tinebase_Model_Container(array_merge($properties, array(
            'type'              => $containerType,
            'backend'           => 'Sql',
            'application_id'    => $this->_getApplication()->getId(),
            'model'             => Tinebase_Core::getApplicationInstance($this->_applicationName)->getDefaultModel()
        )));

        try {
            $container = Tinebase_Container::getInstance()->addContainer($newContainer);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new \Sabre\DAV\Exception\Forbidden('Permission denied to create directory ' . $properties['name']);
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
        if (!$this->_applicationName) {
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
    
    /**
     * resolve contact_id to Addressbook_Model_Contact
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _getContact($contactId)
    {
        $classCacheId = ($this->_useIdAsName ? 'id' : 'n_fileas') . $contactId;
        
        if (isset(self::$_classCache[__FUNCTION__][$classCacheId])) {
            return self::$_classCache[__FUNCTION__][$classCacheId];
        }
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array(
                'field'     => 'type',
                'operator'  => 'equals',
                'value'     => Addressbook_Model_Contact::CONTACTTYPE_USER
            ),
            array(
                'field'     => $this->_useIdAsName ? 'id' : 'n_fileas',
                'operator'  => 'equals',
                'value'     => $contactId
            ),
        ));
        
        $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
        
        if (!$contact) {
            throw new Tinebase_Exception_NotFound('contact not found');
        }
        
        self::$_classCache[__FUNCTION__][$classCacheId] = $contact;
        
        return $contact;
    }
}
