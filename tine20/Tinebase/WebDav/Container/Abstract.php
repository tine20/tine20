<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to handle containers in Cal/CardDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Container_Abstract extends Sabre_DAV_Collection implements Sabre_DAV_IProperties, Sabre_DAVACL_IACL
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
     * @param string $name
     * @param resource $vcardData
     * @return Sabre_DAV_File
     */
    public function createFile($name, $vobjectData = null) 
    {
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        $object = $objectClass::create($this->_container, $name, $vobjectData);
        
        // avoid sending headers during unit tests
        if (php_sapi_name() != 'cli') {
            // @todo this belongs to DAV_Server, but is currently not supported
            header('ETag: ' . $object->getETag());
            #header('Location: /' . $object->getName());
        }
        
        return $object;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Collection::getChild()
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
                throw new Sabre_DAV_Exception_FileNotFound('Object not found');
            }
        }
        
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        return new $objectClass($this->_container, $object);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
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
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // skip group
                        continue 2;
                    }
                    $principal = 'principals/groups/' . $group->list_id;
                    break;
                    
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    try {
                        $fulluser = Tinebase_User::getInstance()->getFullUserById($grant->account_id);
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
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' webdav acl ' . print_r($acl, true));
        
        return $acl;
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name;
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
        return null;
        return $this->addressBookInfo['principaluri'];
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
        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');
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
        return false;
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
        
        return $id;
    }
}
