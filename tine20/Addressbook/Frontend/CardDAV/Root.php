<?php
/**
 * root of tree for the CardDAV frontend
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * root of tree for the CardDAV frontend
 *
 * This class handles the root of the CardDAV tree
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_Root extends Sabre_DAV_Collection
{
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $children = array(
            $this->getChild(Addressbook_Frontend_CardDAV_Collection_Personal::ROOT_NODE),
            $this->getChild('principals'),
            $this->getChild('shared')
        );
        
        return $children;
        
    }
    
    public function getChild($_name) 
    {
        switch ($_name) {
            case Addressbook_Frontend_CardDAV_Collection_Personal::ROOT_NODE:
                return new Addressbook_Frontend_CardDAV_Collection_Personal('Addressbook');
                
                break;
                
            case 'principals':
                return new Sabre_DAVACL_PrincipalCollection(new Tinebase_WebDav_Principals());
                
                break;
                
            case 'shared':
                return new Addressbook_Frontend_CardDAV_Collection_Shared('Addressbook');
                
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound("child $_name not found");
                break;
        }
    }

    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        // the root has no name
    }
}
