<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV extends Tinebase_WebDav_Collection_AbstractContainerTree
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChild()
     */
    public function getChild($name)
    {
        // do this only for caldav requests
        if ($this->_useIdAsName && count($this->_getPathParts()) == 2 && in_array($name, array('inbox', 'outbox'))) {
            switch ($name) {
                case 'inbox':
                    return new Calendar_Frontend_CalDAV_ScheduleInbox(Tinebase_Core::getUser());
                    
                    break;
                    
                case 'outbox':
                    return new \Sabre\CalDAV\Schedule\Outbox('principals/users/' . Tinebase_Core::getUser()->contact_id);
                    
                    break;
            }
        }
        
        return parent::getChild($name);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChildren()
     */
    public function getChildren()
    {
        $children = parent::getChildren();
        
        // do this only for caldav request 
        if ($this->_useIdAsName && count($this->_getPathParts()) == 2) {
            $tfwdavct = new Tasks_Frontend_WebDAV('tasks/' . array_value(1, $this->_getPathParts()), $this->_useIdAsName);
            
            $children = array_merge($children, $tfwdavct->getChildren());
        }
        
        return $children;
    }
}
