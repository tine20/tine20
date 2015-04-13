<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle imports in Sales application
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Sales_Frontend_WebDAV_Import extends \Sabre\DAV\Collection implements \Sabre\DAV\IProperties, \Sabre\DAVACL\IACL
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
     * contructor
     * 
     * @param string $path         the current path
     * @param bool   $useIdAsName  use name or id as node name
     */
    public function __construct($path)
    {
        $this->_path = $path;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\Collection::createFile()
     */
    public function createFile($name, $data = null)
    {
        $invoice = Sales_Controller_PurchaseInvoice::getInstance()->importPurchaseInvoice($name, $data);
        
        return '"' . $invoice->seq . '"';
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        throw new Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
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
        
        return $children;
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
        list(,$name) = Sabre\DAV\URLUtil::splitPath($this->_path);
        
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
}
