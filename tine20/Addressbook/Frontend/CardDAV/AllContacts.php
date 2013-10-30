<?php

use Sabre\VObject;

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle all contacts meta addressbook in CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_AllContacts extends Sabre\DAV\Collection implements Sabre\DAV\IProperties, Sabre\DAVACL\IACL, Sabre\CardDAV\IAddressBook
{
    const NAME = 'contacts';
    
    /**
     * translated container name
     * @var string
     */
    protected $_containerName;
    
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    
    public function __construct($_userId)
    {
        $this->_user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : Tinebase_User::getInstance()->get($_userId);
        $this->_containerName = Tinebase_Translation::getTranslation('Addressbook')->_('All Contacts');
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $combinedSequence = 0;
        $containers = Tinebase_Container::getInstance()->getContainerByACL($this->_user, 'Addressbook', Tinebase_Model_Grants::GRANT_SYNC);
        foreach ($containers as $container) {
            $combinedSequence += $container->content_seq;
        }
        
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => $combinedSequence,
            'id'                                     => self::NAME,
            'uri'                                    => self::NAME,
            '{DAV:}resource-id'                      => 'urn:uuid:' . self::NAME,
            '{DAV:}owner'                            => new Sabre\DAVACL\Property\Principal(Sabre\DAVACL\Property\Principal::HREF, 'principals/users/' . $this->_user->contact_id),
            '{DAV:}displayname'                      => $this->_containerName,
             
            #'principaluri'      => $principalUri,
            '{' . Sabre\CardDAV\Plugin::NS_CARDDAV . '}addressbook-description'    => 'Addressbook ' . $this->_containerName,
            '{' . Sabre\CardDAV\Plugin::NS_CARDDAV . '}supported-addressbook-data' => new Sabre\CardDAV\Property\SupportedAddressData(array(array('contentType' => 'text/vcard', 'version' => '3.0')))
        );

        $response = array();

        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }

        return $response;
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
        $objectClass = 'Addressbook_Frontend_WebDAV_Contact';
        
        $container = Tinebase_Container::getInstance()->getDefaultContainer('Addressbook_Model_Contact', $this->_user);
        $object = $objectClass::create($container, $name, $vobjectData);
    
        return $object->getETag();
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        $modelName = 'Addressbook_Model_Contact';
    
        if ($_name instanceof $modelName) {
            $object = $_name;
        } else {
            $filterClass = 'Addressbook_Model_ContactFilter';
            $filter = new $filterClass(array(
                array(
                        'field'     => 'id',
                        'operator'  => 'equals',
                        'value'     => $this->_getIdFromName($_name)
                )
            ));
            $object = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, false, 'sync')->getFirstRecord();
    
            if ($object == null) {
                throw new Sabre\DAV\Exception\NotFound('Object not found');
            }
        }
        
        $container = Tinebase_Container::getInstance()->getContainerById($object->container_id);
        $objectClass = 'Addressbook_Frontend_WebDAV_Contact';
    
        return new $objectClass($container, $object);
    }
    
   
    function getChildren()
    {
        $filterClass = 'Addressbook_Model_ContactFilter';
        $filter = new $filterClass(array(
            array(
                'field'     => 'container_id',
                'operator'  => 'equals',
                'value'     => array('path' => '/')
            )
        ));
    
        $objects = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, false, 'sync');
    
        $children = array();
    
        foreach ($objects as $object) {
            $children[] = $this->getChild($object);
        }
    
        return $children;
    }
    
    
    public function getGroup()
    {
        return null;
    }
    
    /**
     * we set all acl's to true an throw exceptions if update is not allowed to a certain contact
     *
     * @return array
     */
    public function getACL()
    {
        $principal = 'principals/users/' . $this->_user->contact_id;
        $acl = array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $principal,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write-content',
                'principal' => $principal,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}bind',
                'principal' => $principal,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}unbind',
                'principal' => $principal,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write-properties',
                'principal' => $principal,
                'protected' => true,
            )
        );
        
        return $acl;
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
    

    public function getOwner()
    {
        return null;
//         return 'principals/users/' . $this->_user->contact_id;
    }

    public function setACL(array $acl)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed("Properties of meta addressbook 'All Contacts' can't be changed");
    }

    public function updateProperties($mutations)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed("Properties of meta addressbook 'All Contacts' can't be changed");
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
     *
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}