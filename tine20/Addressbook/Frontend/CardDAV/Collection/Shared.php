<?php
/**
* class to handle shared folders in CardDAV tree
*
* @package     Addressbook
* @subpackage  Frontend
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @author      Lars Kneschke <l.kneschke@metaways.de>
* @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
*
*/

/**
 * class to handle shared folders in CardDAV tree
 *
 * @todo  yet unfinished
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_Collection_Shared extends Sabre_DAV_Collection
{
    protected $_application;
    protected $_path;
    
    public function __construct($_application, $_path = null)
    {
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        $this->_path = $_path;
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $children = array(
            $this->getChild('personal'),
            $this->getChild('principals'),
            $this->getChild('shared')
        );
        
        return $children;
        
    }
    
    public function getChild($_name) 
    {
        switch ($_name) {
            case 'carddav':
                return $this;
                break;
                
            case 'personal':
                return new Addressbook_Frontend_CardDAV_Root();
                
                #$principalBackend = new Tinebase_WebDav_Principals();
                #$carddavBackend   = new Addressbook_Frontend_CardDAV_Backend();
                
                #return new Sabre_CardDAV_AddressBookRoot($principalBackend, $carddavBackend);
                break;
                
            case 'principals':
                $principalBackend = new Tinebase_WebDav_Principals();
                
                return new Sabre_DAVACL_PrincipalCollection($principalBackend);
                break;
                
            case 'shared':
                return new Addressbook_Frontend_CardDAV_Root();
                
                #$principalBackend = new Tinebase_WebDav_Principals();
                #$carddavBackend   = new Addressbook_Frontend_CardDAV_Backend();
                
                #return new Sabre_CardDAV_AddressBookRoot($principalBackend, $carddavBackend);
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
        if ($this->_path == null) {
            return 'shared';
        } else {
            return basename($this->_path);
        }
    }
}
