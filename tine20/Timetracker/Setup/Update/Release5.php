<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Timetracker_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>not yet billed</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration);
        
        $this->setTableVersion('timetracker_timeaccount', 7);
        $this->setApplicationVersion('Timetracker', '5.1');
    }
}
