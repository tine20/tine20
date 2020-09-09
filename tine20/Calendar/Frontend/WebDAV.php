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
    protected $_model = Calendar_Model_Event::class;

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\IExtendedCollection::createExtendedCollection()
     * @throws \Sabre\DAV\Exception\Forbidden
     */
    public function createExtendedCollection($name, array $resourceType, array $properties)
    {
        if (count($this->_getPathParts()) === 2 && isset($properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'])) {
            $componentSet = $properties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'];

            if ($componentSet instanceof \Sabre\CalDAV\Property\SupportedCalendarComponentSet &&
                in_array('VTODO', $componentSet->getValue())) {

                if (Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN)) {
                    $tasks = new Tasks_Frontend_WebDAV('tasks/' . $this->getName(), $this->_useIdAsName);
                } else {
                    throw new  \Sabre\DAV\Exception\Forbidden('Tasks not allowed for user');
                }
                
                return $tasks->createExtendedCollection($name, $resourceType, $properties);
            }
        }
        
        return parent::createExtendedCollection($name, $resourceType, $properties);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChild()
     */
    public function getChild($name)
    {
        if (isset($this->_instanceCache[__FUNCTION__][$name])) {
            return $this->_instanceCache[__FUNCTION__][$name];
        }

        $child = null;
        // do this only for caldav requests
        if ($this->_useIdAsName && count($this->_getPathParts()) == 2 && in_array($name, array('inbox', 'outbox', 'dropbox'))) {
            switch ($name) {
                case 'inbox':
                    $child = new Calendar_Frontend_CalDAV_ScheduleInbox(Tinebase_Core::getUser());
                    break;
                    
                case 'outbox':
                    $child = new \Sabre\CalDAV\Schedule\Outbox('principals/users/' . Tinebase_Core::getUser()->contact_id);
                    break;
                    
                case 'dropbox':
                    $child = new Calendar_Frontend_CalDAV_Dropbox(Tinebase_Core::getUser());
                    break;
            }
        }

        if (null === $child) {
            $child = parent::getChild($name);
        }

        $this->_instanceCache[__FUNCTION__][$name] = $child;

        return $child;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_WebDav_Collection_AbstractContainerTree::getChildren()
     */
    public function getChildren()
    {
        if (isset($this->_instanceCache[__FUNCTION__])) {
            return $this->_instanceCache[__FUNCTION__];
        }

        $children = parent::getChildren();
        
        // do this only for caldav request 
        if ($this->_useIdAsName && count($this->_getPathParts()) == 2 && Tinebase_Core::getUser()->hasRight('Tasks', Tinebase_Acl_Rights::RUN)) {
            $tfwdavct = new Tasks_Frontend_WebDAV('tasks/' . Tinebase_Helper::array_value(1, $this->_getPathParts()), $this->_useIdAsName);
            
            $children = array_merge($children, $tfwdavct->getChildren());
        }

        $this->_instanceCache[__FUNCTION__] = $children;

        return $children;
    }
}
