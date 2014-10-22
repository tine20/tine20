<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_WebDAV extends Tinebase_WebDav_Collection_AbstractContainerTree
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChild()
     */
    public function getChild($name)
    {
        if (count($this->_getPathParts()) === 2 && $name == Addressbook_Frontend_CardDAV_AllContacts::NAME) {
            return new Addressbook_Frontend_CardDAV_AllContacts(Tinebase_Core::getUser());
        }
        
        return parent::getChild($name);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChildren()
     */
    public function getChildren()
    {
        list ($client, $version) = Addressbook_Convert_Contact_VCard_Factory::getUserAgent();
        
        if (count($this->_getPathParts()) === 2 && in_array($client, array(Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX))) {
            $children[] = $this->getChild(Addressbook_Frontend_CardDAV_AllContacts::NAME);
            
            return $children;
        }
        
        return parent::getChildren();
    }
}
