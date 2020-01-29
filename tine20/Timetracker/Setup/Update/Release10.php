<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

class Timetracker_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * Add fulltext index to field description of timesheet
     */
    public function update_0()
    {
        if ($this->getTableVersion('timetracker_timesheet') < 6) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>description</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>description</name>
                    </field>
                </index>
            ');

            try {
                $this->_backend->addIndex('timetracker_timesheet', $declaration);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }

            $this->setTableVersion('timetracker_timesheet', '6');
        }
        $this->setApplicationVersion('Timetracker', '10.1');
    }

    /**
     * update to 10.2
     *
     * Add fulltext index to field description of timeaccount
     */
    public function update_1()
    {
        if ($this->getTableVersion('timetracker_timesheet') < 11) {
            $declaration = new Setup_Backend_Schema_Index_Xml('
                <index>
                    <name>description</name>
                    <fulltext>true</fulltext>
                    <field>
                        <name>description</name>
                    </field>
                </index>
            ');

            try {
                $this->_backend->addIndex('timetracker_timeaccount', $declaration);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
            $this->setTableVersion('timetracker_timeaccount', '11');
        }
        $this->setApplicationVersion('Timetracker', '10.2');
    }

    public function update_2()
    {
        if ($this->getTableVersion('timetracker_timeaccount') < 12) {
            $this->setTableVersion('timetracker_timeaccount', 12);
        }
        $this->setApplicationVersion('Timetracker', '10.3');
    }

    /**
     * update to 10.4
     *
     * @return void
     */
    public function update_3()
    {
        if (! $this->_backend->tableExists('timetracker_timeaccount_fav')) {
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
        }
        $this->setApplicationVersion('Timetracker', '10.4');
    }

    /**
     * 0012470: Don't shorten description in export
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Timetracker'));
        $this->setApplicationVersion('Timetracker', '10.5');
    }

    /**
     * update to 10.6
     *
     * Add fulltext index to field description of timesheet
     * - re-run update 0 + 1 because 2017.02 added update 2 + 3
     */
    public function update_5()
    {
        $this->update_0();
        $this->update_1();
        $this->setApplicationVersion('Timetracker', '10.6');
    }

    /**
     * update to 11.0
     */
    public function update_6()
    {
        $this->setApplicationVersion('Timetracker', '11.0');
    }
}
