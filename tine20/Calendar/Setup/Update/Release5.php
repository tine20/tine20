<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>transp</name>
                <type>text</type>
                <length>40</length>
                <default>OPAQUE</default>
            </field>');
        $this->_backend->alterCol('cal_events', $declaration);
        $this->setTableVersion('cal_events', 4);
        
        if ($this->getTableVersion('cal_attendee') == 1) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>user_type</name>
                    <type>text</type>
                    <length>32</length>
                    <default>user</default>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->alterCol('cal_attendee', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>role</name>
                    <type>text</type>
                    <length>32</length>
                    <default>REQ</default>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->alterCol('cal_attendee', $declaration);
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>status</name>
                    <type>text</type>
                    <length>32</length>
                    <default>NEEDS-ACTION</default>
                    <notnull>true</notnull>
                </field>');
            $this->_backend->alterCol('cal_attendee', $declaration);
            $this->setTableVersion('cal_attendee', 2);
        }
        
        $this->setApplicationVersion('Calendar', '5.1');
    }
    
    /**
     * update to 5.2
     * - move attendee roles + status records in config
     */
    public function update_1()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $attendeeRolesConfig = array(
            'name'    => Calendar_Config::ATTENDEE_ROLES,
            'records' => array(
                array('id' => 'REQ', 'value' => 'Required', 'system' => true), //_('Required')
                array('id' => 'OPT', 'value' => 'Optional', 'system' => true), //_('Optional')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_ROLES,
            'value'             => json_encode($attendeeRolesConfig),
        )));
        
        $attendeeStatusConfig = array(
            'name'    => Calendar_Config::ATTENDEE_STATUS,
            'records' => array(
                array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true), //_('No response')
                array('id' => 'ACCEPTED',     'value' => 'Accepted',    'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Accepted')
                array('id' => 'DECLINED',     'value' => 'Declined',    'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Declined')
                array('id' => 'TENTATIVE',    'value' => 'Tentative',   'icon' => 'images/calendar-response-tentative.png',               'system' => true), //_('Tentative')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => Calendar_Config::ATTENDEE_STATUS,
            'value'             => json_encode($attendeeStatusConfig),
        )));
        
        $this->setApplicationVersion('Calendar', '5.2');
    }
    
    /**
     * update to 5.3
     * - empty rrule -> NULL
     */
    public function update_2()
    {
        $tablePrefix = SQL_TABLE_PREFIX;
        
        $this->_db->query("UPDATE {$tablePrefix}cal_events SET `rrule`=NULL WHERE `rrule` LIKE ''");
        
        $this->setApplicationVersion('Calendar', '5.3');
    }
    
    /**
     * update to 5.4
     * - enum -> text
     */
    public function update_3()
    {
        if ($this->getTableVersion('cal_attendee') == 2) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>transp</name>
                    <type>text</type>
                    <length>40</length>
                    <default>OPAQUE</default>
                </field>
            ');
            $this->_backend->addCol('cal_attendee', $declaration);
            
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>alarm_ack_time</name>
                    <type>datetime</type>
                </field>
            ');
            $this->_backend->addCol('cal_attendee', $declaration);
            
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>alarm_snooze_time</name>
                    <type>datetime</type>
                </field>
            ');
            $this->_backend->addCol('cal_attendee', $declaration);
            
            $this->setTableVersion('cal_attendee', 3);
        }
        
        $this->setApplicationVersion('Calendar', '5.4');
    }
    
    /**
     * update to 5.5
     * - change length of uid field
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>uid</name>
                <type>text</type>
                <length>255</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('cal_events', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>recurid</name>
                <type>text</type>
                <length>255</length>
            </field>
        ');
        $this->_backend->alterCol('cal_events', $declaration);
        
        $this->setTableVersion('cal_events', 5);
        
        $this->setApplicationVersion('Calendar', '5.5');
    }

    public function update_5()
    {
        // alter status_id -> status
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>');
        
        $this->_backend->alterCol('cal_events', $declaration, 'status_id');
        
        $this->setTableVersion('cal_events', 6);
        
        $this->setApplicationVersion('Calendar', '5.6');
    }

        
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_6()
    {
        $this->setApplicationVersion('Calendar', '6.0');
    }
}
