<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Calendar Acl Filter
 * 
 * Manages implicit grants of participants and organizers
 * 
 * @package Calendar
 */
class Calendar_Model_EventAclFilter extends Tinebase_Model_Filter_Container
{
    /**
     * appeds sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        parent::appendFilterSql($_select, $_backend);
        
        $db = $_backend->getAdapter();
        $currentUserId = Tinebase_Core::getUser()->getId();
        
        // organizer gets all grants implicitly 
        $_select->orWhere($db->quoteIdentifier('organizer') . ' = ?', $currentUserId, Zend_Db::INT_TYPE);
        
        // participants get read grant implicitly
        if (! in_array(Tinebase_Model_Container::GRANT_EDIT, $this->_requiredGrants)) {
            $_select->joinLeft(
                /* table  */ array('attendee' => $_backend->getTablePrefix() . 'cal_attendee'), 
                /* on     */ $db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $db->quoteIdentifier($_backend->getTableName() . '.id') . ' AND (' . 
                                '( ' . $db->quoteInto($db->quoteIdentifier('attendee.user_type') . ' = ? ',  Calendar_Model_Attendee::USERTYPE_USER) . ' AND ' . $db->quoteInto($db->quoteIdentifier('attendee.user_id') . ' = ? ',  $currentUserId, Zend_Db::INT_TYPE) . ' ) OR ' .
                                '( ' . $db->quoteInto($db->quoteIdentifier('attendee.user_type') . ' = ? ',  Calendar_Model_Attendee::USERTYPE_GROUP) . ' AND ' . $db->quoteInto($db->quoteIdentifier('attendee.user_id') . ' IN (?) ',  Tinebase_Group::getInstance()->getGroupMemberships($currentUserId), Zend_Db::INT_TYPE) . ' )' .
                             ')',
                /* select */ array());
    
            $_select->orWhere($db->quoteIdentifier('attendee.user_id') . ' IS NOT NULL');
        }
    }
}