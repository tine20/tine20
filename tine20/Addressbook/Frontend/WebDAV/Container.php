<?php
use Sabre\DAVACL;
use Sabre\CardDAV;

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
class Addressbook_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract implements Sabre\CardDAV\IAddressBook
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
        $ctags = Tinebase_Container::getInstance()->getContentSequence($this->_container);
        
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => $ctags,
            'id'                                     => $this->_container->getId(),
            'uri'                                    => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
            '{DAV:}resource-id'                      => 'urn:uuid:' . $this->_container->getId(),
            '{DAV:}owner'                            => new DAVACL\Property\Principal(DAVACL\Property\Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
            '{DAV:}displayname'                      => $this->_container->name,
         
            #'principaluri'      => $principalUri,
            '{' . CardDAV\Plugin::NS_CARDDAV . '}addressbook-description'    => 'Addressbook ' . $this->_container->name,
            '{' . CardDAV\Plugin::NS_CARDDAV . '}supported-addressbook-data' => new CardDAV\Property\SupportedAddressData(array(array('contentType' => 'text/vcard', 'version' => '3.0')))
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
