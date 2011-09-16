<?php
/**
 * class to handle personal folders in CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle personal folders in CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_Collection_Personal extends Sabre_DAV_Collection implements Sabre_CardDAV_IAddressBook, Sabre_DAV_IProperties, Sabre_DAVACL_IACL
{
    /**
     * name of the personal tree top node
     * 
     * @var string
     */
    const ROOT_NODE = 'personal';
    
    /**
     * the current application object
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
     * the current path
     * 
     * @var string
     */
    protected $_path;
    
    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_path         the current path
     */
    public function __construct($_application, $_path = '/personal')
    {
        $this->_application = $_application instanceof Tinebase_Model_Application ? $_application : Tinebase_Application::getInstance()->getApplicationByName($_application);
        $this->_path = $_path;
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
    public function createFile($name, $vcardData = null) 
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        $container = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $pathParts[2], Tinebase_Model_Container::TYPE_PERSONAL);
        
        $card = Addressbook_Frontend_CardDAV_Card::create($container, $vcardData);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        
        $children = array();
        
        switch(count($pathParts)) {
            # /personal
            # list users
            case 1:
                $children[] = $this->getChild(Tinebase_Core::getUser());
                break;
            
            # /personal/loginname
            # list container
            case 2:
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_application->name, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ); 
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container);
                }
                break;
                
            # /personal/loginname/foldername
            # list contacts
            case 3:
                $container = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $pathParts[2], Tinebase_Model_Container::TYPE_PERSONAL);
                
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => 'container_id',
                        'operator'  => 'equals',
                        'value'     => $container->getId()
                    )
                ));
                
                $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
                
                foreach ($contacts as $contact) {
                    $children[] = $this->getChild($contact);
                }
                break;
            
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
     * (non-PHPdoc)
     * @see Sabre_DAV_Collection::getChild()
     */
    public function getChild($_name) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name);
        
        if (!is_object($_name)) {
            $pathParts = explode('/', trim($this->_path, '/'));
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
            switch(count($pathParts)) {
                case 1:
                    $_name = Tinebase_Core::getUser();
                    break;
                case 2;
                    $_name = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $_name, Tinebase_Model_Container::TYPE_PERSONAL);
                    break;
                case 3:
                    try {
                        $_name = Addressbook_Controller_Contact::getInstance()->get(str_replace('.vcf', '', $_name));
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new Sabre_DAV_Exception_FileNotFound('Card not found');
                    }
                    break;
            }
        }
        
        if ($_name instanceof Tinebase_Model_User) {
            return new Addressbook_Frontend_CardDAV_Collection_Personal($this->_application, $this->_path . '/' . $_name->accountLoginName);
        } elseif ($_name instanceof Tinebase_Model_Container) {
            return new Addressbook_Frontend_CardDAV_Collection_Personal($this->_application, $this->_path . '/' . $_name->name);
        } elseif ($_name instanceof Tinebase_Record_Abstract) {
            return new Addressbook_Frontend_CardDAV_Card($_name);
        }
        
        throw new Sabre_DAV_Exception_FileNotFound('Child not found');
    }

    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return basename($this->_path);
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
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested,
     * encoded in clark-notation {xmlnamespace}tagname
     *
     * If the array is empty, it means 'all properties' were requested.
     *
     * @param array $properties 
     * @return void
     */
    public function getProperties($properties) 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($properties, true));
        
        $intProp = array(
            '{http://calendarserver.org/ns/}getctag' => time()
        );
        
        $response = array();
        
        foreach($properties as $propertyName) {

            if (isset($intProp[$propertyName])) {

                $response[$propertyName] = $intProp[$propertyName];

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
