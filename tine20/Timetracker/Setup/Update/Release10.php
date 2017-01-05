<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 */
class Timetracker_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @return void
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>timetracker_timeaccount_fav</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>timeaccount_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
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
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>timesheet_favorites--timesheet_id::id</name>
                    <field>
                        <name>timeaccount_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>timetracker_timeaccount</table>
                        <field>id</field>
                    </reference>
                </index>
                <index>
                    <name>timesheet_favorites--account_id::id</name>
                    <field>
                        <name>account_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>accounts</table>
                        <field>id</field>
                        <ondelete>CASCADE</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');

        $this->_backend->createTable($declaration, 'Timetracker', 'timetracker_timeaccount_fav');

        $this->setTableVersion('timetracker_timeaccount_fav', 1);
        $this->setApplicationVersion('Timetracker', '10.1');
    }

    /**
     * 0012470: Don't shorten description in export
     */
    public function update_1()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Timetracker'));
        $this->setApplicationVersion('Timetracker', '10.2');
    }
}
