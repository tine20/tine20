<?php

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CalDAV;

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren()
    {
        $calendars = parent::getChildren();
        
        $TFCalDAV = new Tasks_Frontend_CalDAV();
        $todoLists = $TFCalDAV->getChildren();
        
        return array_merge($calendars, $todoLists);
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     * 
     * @throws Sabre\DAV\Exception
     * @throws Sabre\DAV\Exception\NotFound
     */
    public function getChild($_name)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            throw new Sabre\DAV\Exception('PHP 5.3+ is needed for CalDAV support.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name);
    
        $pathParts = explode('/', trim($this->_path, '/'));
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        switch(count($pathParts)) {
            # path == /account->contact_id
            # list container
            case 1:
                if ($_name == 'inbox') {
                    return new Calendar_Frontend_CalDAV_ScheduleInbox(Tinebase_Core::getUser());
                    
                } elseif ($_name == 'outbox') {
                    return new CalDAV\Schedule\Outbox('principals/users/' . Tinebase_Core::getUser()->contact_id);
                    
                } else {
                    try {
                        //modified to getchild for new collection or existing collection
                        if ($_name instanceof Tinebase_Model_Container) {
                            $container = $_name;
                        } else if (is_numeric($_name)) {
                            $container = Tinebase_Container::getInstance()->getContainerById($_name);
                        } else {
                            $container = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $_name, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
                        }
                        
                        //$container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerById($_name);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new Sabre\DAV\Exception\NotFound('Directory not found');
                    } catch (Tinebase_Exception_InvalidArgument $teia) {
                        // invalid container id provided
                        throw new Sabre\DAV\Exception\NotFound('Directory not found');
                    }
                    
                    if (!Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_READ) || !Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_SYNC)) {
                        throw new Sabre\DAV\Exception\NotFound('Directory not found');
                    }
                    
                    $objectClass = array_value(0, explode('_', $container->model ?: 'Calendar')) . '_Frontend_WebDAV_Container';
                    
                    return new $objectClass($container, true);
                }
                
                break;
            
            default:
                throw new Sabre\DAV\Exception\NotFound('Child not found');
            
                break;
        }   
    }    
}
