<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Calendar_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration, 'created_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration, 'deleted_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>organizer</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration, 'organizer');
        
        $this->setTableVersion('cal_events', 2);
        $this->setApplicationVersion('Calendar', '0.2');
    }
    
    /**
     * changes attendee ids of type user from account ids to contact ids
     * 
     */
    public function update_2()
    {
    	$select = $this->_db->select()
    	   ->distinct()
    	   ->from(array('attendee' => SQL_TABLE_PREFIX . 'cal_attendee'), 'user_id')
    	   ->join(array('contacts' => SQL_TABLE_PREFIX . 'addressbook'), $this->_db->quoteIdentifier('attendee.user_id') . ' = ' . $this->_db->quoteIdentifier('contacts.account_id'), 'id')
    	   ->where('user_type = "user"');
    	
        $currentAttendeeIds = $this->_db->fetchAssoc($select);
        
        foreach ($currentAttendeeIds as $attender) {
        	$attenderAccountId = $attender['user_id'];
        	$attenderContactId = $attender['id'];
        	
        	$this->_db->update(SQL_TABLE_PREFIX . 'cal_attendee', array('user_id' => $attenderContactId), $this->_db->quoteIdentifier('user_id') . ' = ' . $attenderAccountId);
        }
    	
        $this->setApplicationVersion('Calendar', '0.3');
    }
}
