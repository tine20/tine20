<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in CardDAV tree
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
    
    protected $_model;
    
    protected $_suffix = '.vcf';
    
    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_container    the current path
     */
    public function __construct(Tinebase_Model_Application $_application, $_model, Tinebase_Model_Container $_container)
    {
        $this->_application = $_application;
        $this->_model       = $_model;
        $this->_container   = $_container;
    }
    
    /**
    * Creates a new file
    *
    * The contents of the new file must be a valid VCARD
    *
    * @param string $name
    * @param resource $vcardData
    * @return void
    */
    public function createFile($name, $vobjectData = null) 
    {
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        $object = $objectClass::create($this->_container, $vobjectData);
    }
    
    /**
    * (non-PHPdoc)
    * @see Sabre_DAV_Collection::getChild()
    */
    public function getChild($_name)
    {
        $modelName = $this->_application->name . '_Model_' . $this->_model;
        $controller = Tinebase_Core::getApplicationInstance($this->_application->name, $this->_model);
        
        try {
            $object = $_name instanceof $modelName ? $_name : $controller->get(str_replace($this->_suffix, '', $_name));
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre_DAV_Exception_FileNotFound('Object not found');
        }
        
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        return new $objectClass($object);
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
        
        $controller = Tinebase_Core::getApplicationInstance($this->_application->name, $this->_model);
        $objects = $controller->search($filter);
        
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
    public function getACL() {
    
        return array(
            array(
                        'privilege' => '{DAV:}read',
                        'principal' => $this->addressBookInfo['principaluri'],
                        'protected' => true,
            ),
            array(
                        'privilege' => '{DAV:}write',
                        'principal' => $this->addressBookInfo['principaluri'],
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
        return null;
        return $this->addressBookInfo['principaluri'];
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        list(, $basename) = Sabre_DAV_URLUtil::splitPath($this->_path);
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => time(),
            'id'                => $this->_container->getId(),
            'uri'               => $this->_container->name,
            #'principaluri'      => $principalUri,
            #'{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => $this->_container->description,
            '{DAV:}displayname' => $this->_container->name,
        );
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($requestedProperties, true));
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($properties, true));
        
        $response = array();
    
        foreach($requestedProperties as $prop) switch($prop) {
            case '{DAV:}owner' :
                $response[$prop] = new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF,$this->calendarInfo['principaluri']);
                break;
                
            default :
                if (isset($properties[$prop])) $response[$prop] = $properties[$prop];
                break;
    
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($response, true));
        
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
        return $this->carddavBackend->updateAddressBook($this->addressBookInfo['id'], $mutations); 
    }
}
