<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract implements Sabre_CardDAV_IAddressBook
{
    protected $_applicationName = 'Addressbook';
    
    protected $_model = 'Contact';
    
    protected $_suffix = '.vcf';
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $displayName = $this->_container->type == Tinebase_Model_Container::TYPE_SHARED ? $this->_container->name . ' (shared)' : $this->_container->name;
        
        $ctags = Tinebase_Container::getInstance()->getContentSequence($this->_container);
        
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => $ctags[$this->_container->getId()],
            'id'                                     => $this->_container->getId(),
            'uri'                                    => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
            '{DAV:}resource-id'                      => 'urn:uuid:' . $this->_container->getId(),
            '{DAV:}owner'                            => new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
        	'{DAV:}displayname'                      => $displayName,
         
            #'principaluri'      => $principalUri,
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description'    => 'Addressbook ' . $displayName,
            '{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}supported-addressbook-data' => new Sabre_CardDAV_Property_SupportedAddressData(array(array('contentType' => 'text/vcard', 'version' => '3.0')))
        );
        
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
        
        return $response;
    }
}
