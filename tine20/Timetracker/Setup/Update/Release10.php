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
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        $this->_backend->addIndex('timetracker_timesheet', $declaration);

        $this->setTableVersion('timetracker_timesheet', '6');
        $this->setApplicationVersion('Timetracker', '10.1');
    }

    /**
     * update to 10.2
     *
     * Add fulltext index to field description of timesheet
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        $this->_backend->addIndex('timetracker_timeaccount', $declaration);

        $this->setTableVersion('timetracker_timeaccount', '11');
        $this->setApplicationVersion('Timetracker', '10.2');
    }
}
