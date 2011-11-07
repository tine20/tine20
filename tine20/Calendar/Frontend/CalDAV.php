<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_CalDAV extends Addressbook_Frontend_CardDAV
{
    protected $_applicationName = 'Calendar';
    
    protected $_model = 'Event';    
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Collection::getChild()
     */
    public function getChild($_name)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name);
    
        $pathParts = explode('/', trim($this->_path, '/'));
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        switch(count($pathParts)) {
            # path == /account->contact_id
            # list container
            case 1:
                if ($_name == 'schedule-inbox') {
                    return new Calendar_Frontend_CalDAV_ScheduleInbox(Tinebase_Core::getUser());
                } elseif ($_name == 'schedule-outbox') {
                    return new Calendar_Frontend_CalDAV_ScheduleOutbox(Tinebase_Core::getUser());
                } else {
                    try {
                        $container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerById($_name);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new Sabre_DAV_Exception_FileNotFound('Directory not found');
                    }
                    
                    $objectClass = $this->_application->name . '_Frontend_WebDAV_Container';
                    
                    return new $objectClass($container, true);
                }
                
                break;
            
            default:
                throw new Sabre_DAV_Exception_FileNotFound('Child not found');
            
                break;
        }
    
        
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $pathParts = explode('/', trim($this->_path, '/'));
    
        $children = array();
    
        switch(count($pathParts)) {
            # path == /account->contact_id
            # list container
            case 1:
                $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_application->name, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_READ);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container);
                }
            
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_application->name, Tinebase_Model_Grants::GRANT_READ);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container);
                }
                
                // add special folders schedule-inbox and schedule-outbox
                $children[] = $this->getChild('schedule-inbox');
                $children[] = $this->getChild('schedule-outbox');
            
                break;
        }
    
        return $children;
    
    }
    
}
