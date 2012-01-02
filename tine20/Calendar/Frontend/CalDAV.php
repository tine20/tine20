<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * 
     * @throws Sabre_DAV_Exception
     * @throws Sabre_DAV_Exception_FileNotFound
     */
    public function getChild($_name)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            throw new Sabre_DAV_Exception('PHP 5.3+ is needed for CalDAV support.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name);
    
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
                    
                    if (!Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_READ) || !Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_SYNC)) {
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
}
