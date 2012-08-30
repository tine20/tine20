<?php
/**
 * class to handle a single vcard
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle a single vcard
 *
 * This class handles the creation, update and deletion of vcards
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_WebDAV_Contact extends Sabre_DAV_File implements Sabre_CardDAV_ICard, Sabre_DAVACL_IACL
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_contact;
    
    /**
     * holds the vcard returned to the client
     * 
     * @var string
     */
    protected $_vcard;
    
    /**
     * @var Addressbook_Convert_Contact_VCard_Interface
     */
    protected $_converter;
    
    /**
     * Constructor 
     * 
     * @param  string|Addressbook_Model_Contact  $_contact  the id of a contact or the contact itself 
     */
    public function __construct(Tinebase_Model_Container $_container, $_contact = null) 
    {
        $this->_container = $_container;
        $this->_contact = $_contact;

        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $this->_converter = Addressbook_Convert_Contact_VCard_Factory::factory($backend, $version);
    }
    
    /**
     * this function creates a Addressbook_Model_Contact and stores it in the database
     * 
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string           $vcardData
     */
    public static function create(Tinebase_Model_Container $container, $name, $vcardData)
    {
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory($backend, $version);
        
        $contact = $converter->toTine20Model($vcardData);
        $contact->container_id = $container->getId();
        
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        $contact->setId($id);
        
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        
        $card = new self($container, $contact);
        
        return $card;
    }
    
    /**
     * Deletes the card
     *
     * @return void
     * 
     * @see Calendar_Frontend_WebDAV_Event::delete()
     */
    public function delete() 
    {
        // when a move occurs, thunderbird first sends to delete command and immediately a put command
        // we must delay the delete command, otherwise the put command fails
        sleep(1);
        
        // (re) fetch contact as tree move does not refresh src node before delete
        // check if we are still in the same container, if not -> it is a MOVE 
        $contact = Addressbook_Controller_Contact::getInstance()->get($this->_contact);
        
        if ($contact->container_id == $this->_container->getId()) {
            Addressbook_Controller_Contact::getInstance()->delete($contact);
        }
    }
    
    /**
     * Returns the VCard-formatted object 
     * 
     * @return stream
     */
    public function get() 
    {
        $s = fopen('php://temp','r+');
        fwrite($s, $this->_getVCard());
        rewind($s);
        
        return $s;
    }
    
    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() 
    {
        return $this->getRecord()->getId() . '.vcf';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @todo add real owner
     * @return string|null
     */
    public function getOwner() 
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo add real group
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
     * @todo add the real logic
     * @return array 
     */
    public function getACL() 
    {
        return null;
        
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
            ),
        );

    }
    
    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {
    
        return 'text/vcard';
    
    }
    
    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() 
    {
        return '"' . md5($this->getRecord()->getId() . $this->getLastModified()) . '"';
    }
    
    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return time
     */
    public function getLastModified() 
    {
        return ($this->getRecord()->last_modified_time instanceof Tinebase_DateTime) ? $this->getRecord()->last_modified_time->toString() : $this->getRecord()->creation_time->toString();
    }
    
    /**
     * Returns the size of the vcard in bytes
     *
     * @return int
     */
    public function getSize() 
    {
        return strlen($this->_getVCard());
    }
    
    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return void
     */
    public function put($cardData) 
    {
        if (get_class($this->_converter) == 'Addressbook_Convert_Contact_VCard_Generic') {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " update by generic client not allowed. See Addressbook_Convert_Contact_VCard_Factory for supported clients.");
            throw new Sabre_DAV_Exception_Forbidden('Update denied for unknow client');
        }
        
        $contact = $this->_converter->toTine20Model($cardData, $this->getRecord());
        
        $this->_contact = Addressbook_Controller_Contact::getInstance()->update($contact, false);
        $this->_vcard   = null;

        // avoid sending headers during unit tests
        if (php_sapi_name() != 'cli') {
            // @todo this belongs to DAV_Server, but is currently not supported
            header('ETag: ' . $this->getETag());
        }
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
     * return Addressbook_Model_Contact and convert contact id to model if needed
     * 
     * @return Addressbook_Model_Contact
     */
    public function getRecord()
    {
        if (! $this->_contact instanceof Addressbook_Model_Contact) {
            $id = ($pos = strpos($this->_contact, '.')) === false ? $this->_contact : substr($this->_contact, 0, $pos);
            
            $this->_contact = Addressbook_Controller_Contact::getInstance()->get($this->id);
        }
        
        return $this->_contact;
    }
    
    /**
     * return vcard and convert Addressbook_Model_Contact to vcard if needed
     * 
     * @return string
     */
    protected function _getVCard()
    {
        if ($this->_vcard == null) {
            $this->_vcard = $this->_converter->fromTine20Model($this->getRecord());
        }
        
        return $this->_vcard->serialize();
    }
}
