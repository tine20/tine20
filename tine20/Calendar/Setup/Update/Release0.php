<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
    
    /**
     * update to version 2.0
     * - add resources table
     */
    public function update_3()
    {
        $tableDefinition = '
        <table>
            <name>cal_resources</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>created_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>last_modified_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>deleted_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>            
                <field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                </field>
                <field>
                    <name>email</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>is_location</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>name</name>
                    <field>
                        <name>name</name>
                    </field>
                </index>
                <index>
                    <name>email</name>
                    <field>
                        <name>email</name>
                    </field>
                </index>
                <index>
                    <name>is_location</name>
                    <field>
                        <name>is_location</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition);
        $this->_backend->createTable($table);
        
        $calendarApp = Tinebase_Application::getInstance()->getApplicationByName('Calendar');
        Tinebase_Application::getInstance()->addApplicationTable($calendarApp, 'cal_resources', 1);
        
        $this->setApplicationVersion('Calendar', '2.0');
    }
}
