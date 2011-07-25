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
        
        $this->setApplicationVersion('Calendar', '5.1');
    }
}
