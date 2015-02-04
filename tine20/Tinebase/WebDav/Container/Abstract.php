<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to handle containers in Cal/CardDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Container_Abstract extends \Sabre\DAV\Collection implements \Sabre\DAV\IProperties, \Sabre\DAVACL\IACL
{
    /**
     * the current application object
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    protected $_applicationName;
    
    protected $_container;
    
    protected $_controller;
    
    protected $_model;
    
    protected $_suffix;
    
    protected $_useIdAsName;
    
    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_container    the current path
     */
    public function __construct(Tinebase_Model_Container $_container, $_useIdAsName = false)
    {
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $this->_container   = $_container;
        $this->_useIdAsName = (boolean)$_useIdAsName;
    }
    
    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid VCARD
     *
     * @param  string    $name
     * @param  resource  $vcardData
     * @return string    the etag of the record
     */
    public function createFile($name, $vobjectData = null) 
    {
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;

        $object = $objectClass::create($this->_container, $name, $vobjectData);
        
        return $object->getETag();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\Node::delete()
     */
    public function delete()
    {
        try {
            Tinebase_Container::getInstance()->deleteContainer($this->_container);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            throw new Sabre\DAV\Exception\Forbidden('Permission denied to delete node');
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        $modelName = $this->_application->name . '_Model_' . $this->_model;
        
        if ($_name instanceof $modelName) {
            $object = $_name;
        } else {
            $filterClass = $this->_application->name . '_Model_' . $this->_model . 'Filter';
            $filter = new $filterClass(array(
                array(
                    'field'     => 'container_id',
                    'operator'  => 'equals',
                    'value'     => $this->_container->getId()
                ),
                array(
                    'field'     => 'id',
                    'operator'  => 'equals',
                    'value'     => $this->_getIdFromName($_name)
                )
            ));
            $object = $this->_getController()->search($filter, null, false, false, 'sync')->getFirstRecord();
            
            if ($object == null) {
                throw new Sabre\DAV\Exception\NotFound('Object not found');
            }
        }
        
        if ($object->has('tags') && !isset($object->tags)) {
            Tinebase_Tags::getInstance()->getTagsOfRecord($object);
        }
        
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        return new $objectClass($this->_container, $object);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre\DAV\INode[]
     */
    function getChildren()
    {
        $filterClass = $this->_application->name . '_Model_' . $this->_model . 'Filter';
        $filter = new $filterClass(array(
            array(
                'field'     => 'container_id',
                'operator'  => 'equals',
                'value'     => $this->_container->getId()
            )
        ));

        /*
         * see http://forge.tine20.org/mantisbt/view.php?id=5122
         * we must use action 'sync' and not 'get' as 
         * otherwise the calendar also return events the user only can see because of freebusy
         */        
        $objects = $this->_getController()->search($filter, null, false, false, 'sync');
        
        $children = array();
        
        foreach ($objects as $object) {
            $children[] = $this->getChild($object);
        }

        return $children;
    }
    
    /**
     * return etag
     * 
     * @return string
     */
    public function getETag()
    {
        return '"' . $this->_container->seq . '"';
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
        $acl    = array();
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container, true);
        
        foreach ($grants as $grant) {
            switch ($grant->account_type) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $principal = 'principals/users/' . Tinebase_Core::getUser()->contact_id;
                    break;
                    
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    try {
                        $group = Tinebase_Group::getInstance()->getGroupById($grant->account_id);
                    } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        // skip group
                        continue 2;
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // skip group
                        continue 2;
                    }
                    
                    $principal = 'principals/groups/' . $group->list_id;
                    
                    break;
                    
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    try {
                        $fulluser = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $grant->account_id, 'Tinebase_Model_FullUser');
                    } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        // skip group
                        continue 2;
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // skip user
                        continue 2;
                    }
                    
                    $principal = 'principals/users/' . $fulluser->contact_id;
                    
                    break;
                    
                default:
                    throw new Tinebase_Exception_UnexpectedValue('unsupported account type');
            }
            
            if($grant[Tinebase_Model_Grants::GRANT_READ] == true) {
                $acl[] = array(
                    'privilege' => '{DAV:}read',
                    'principal' => $principal,
                    'protected' => true,
                );
            }
            if($grant[Tinebase_Model_Grants::GRANT_EDIT] == true) {
                $acl[] = array(
                    'privilege' => '{DAV:}write-content',
                    'principal' => $principal,
                    'protected' => true,
                );
            }
            if($grant[Tinebase_Model_Grants::GRANT_ADD] == true) {
                $acl[] = array(
                    'privilege' => '{DAV:}bind',
                    'principal' => $principal,
                    'protected' => true,
                );
            }
            if($grant[Tinebase_Model_Grants::GRANT_DELETE] == true) {
                $acl[] = array(
                    'privilege' => '{DAV:}unbind',
                    'principal' => $principal,
                    'protected' => true,
                );
            }
            if($grant[Tinebase_Model_Grants::GRANT_ADMIN] == true) {
                $acl[] = array(
                    'privilege' => '{DAV:}write-properties',
                    'principal' => $principal,
                    'protected' => true,
                );
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' webdav acl ' . print_r($acl, true));
        
        return $acl;
    }
    
    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return time
     */
    public function getLastModified() 
    {
        if ($this->_container->last_modified_time instanceof Tinebase_DateTime) {
            return $this->_container->last_modified_time->getTimestamp();
        }
        
        if ($this->_container->creation_time instanceof Tinebase_DateTime) {
            return $this->_container->creation_time->getTimestamp();
        }
        
        return Tinebase_DateTime::now()->getTimestamp();
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        if ($this->_useIdAsName == true) {
            if ($this->_container->uuid) {
                return $this->_container->uuid;
            } else {
                return $this->_container->getId();
            }
        } 
        
        return $this->_container->name;
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo implement real logic
     * @return string|null
     */
    public function getOwner()
    {
        if (! Tinebase_Container::getInstance()->hasGrant(
            Tinebase_Core::getUser(), 
            $this->_container, 
            Tinebase_Model_Grants::GRANT_ADMIN)
        ) {
            return null;
        }
        
        return 'principals/users/' . Tinebase_Core::getUser()->contact_id;
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $properties = array();
        
        $response = array();
        
        foreach ($requestedProperties as $prop) {
            switch($prop) {
                case '{DAV:}getetag':
                    $response[$prop] = $this->getETag();
                    break;
                    
                default:
                    if (isset($properties[$prop])) $response[$prop] = $properties[$prop];
                    break;
            }
        }
        
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
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_ADMIN)) {
            throw new \Sabre\DAV\Exception\Forbidden('permission to update container denied');
        }
        
        $result = array(
            200 => array(),
            403 => array()
        );
        
        foreach ($mutations as $key => $value) {
            switch ($key) {
                case '{DAV:}displayname':
                    if ($value === $this->_container->uuid || $value === $this->_container->getId()) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                            . ' It is not allowed to overwrite the name with the uuid/id');
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                            . ' ' . print_r(array(
                                'useIdAsName' => $this->_useIdAsName,
                                'container'   => $this->_container->toArray(),
                                'new value'   => $value
                            ), true));
                    } else {
                        $this->_container->name = $value;
                    }
                    $result['200'][$key] = null;
                    break;
                    
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-description':
                case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-timezone':
                    // fake success
                    $result['200'][$key] = null;
                    break;
                    
                case '{http://apple.com/ns/ical/}calendar-color':
                    $this->_container->color = substr($value, 0, 7);
                    $result['200'][$key] = null;
                    break;
                
                default:
                    $result['403'][$key] = null;
            }
        }
        
        Tinebase_Container::getInstance()->update($this->_container);
        
        return $result;
    }
    
    /**
     * 
     * @return Tinebase_Controller_Record_Interface
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Tinebase_Core::getApplicationInstance($this->_application->name, $this->_model);
        }
        
        return $this->_controller;
    }
    
    /**
     * get id from name => strip of everything after last dot
     * 
     * @param  string  $_name  the name for example vcard.vcf
     * @return string
     */
    protected function _getIdFromName($_name)
    {
        $id = ($pos = strrpos($_name, '.')) === false ? $_name : substr($_name, 0, $pos);
        $id = strlen($id) > 40 ? sha1($id) : $id;
        
        return $id;
    }
    
    /**
     * generate VTimezone for given folder
     * 
     * @param  string|Tinebase_Model_Application  $applicationName
     * @return string
     */
    public static function getCalendarVTimezone($applicationName)
    {
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, Tinebase_Core::getUser()->getId());
        
        $application = $applicationName instanceof Tinebase_Model_Application 
            ? $applicationName 
            : Tinebase_Application::getInstance()->getApplicationByName($applicationName); 
        
        // create vcalendar object with timezone information
        $vcalendar = new \Sabre\VObject\Component\VCalendar(array(
            'PRODID'   => "-//tine20.org//Tine 2.0 {$application->name} V{$application->version}//EN",
            'VERSION'  => '2.0',
            'CALSCALE' => 'GREGORIAN'
        ));
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($timezone));
        
        // Taking out \r to not screw up the xml output
        return str_replace("\r","", $vcalendar->serialize());
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
