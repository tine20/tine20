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
    protected $_suffix = '.vcf';
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
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
}
