<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class Timetracker_Setup_Update_Release2 extends Setup_Update_Abstract
{

    /**
     * changed budget and price to float
     * 
     * @return void
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>budget</name>
                <type>float</type>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration, 'budget');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>price</name>
                <type>float</type>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration, 'price');

        $this->setTableVersion('timetracker_timeaccount', '5');

        $this->setApplicationVersion('Timetracker', '2.1');
    }

    /**
     * add deadline field
     * 
     * @return void
     */
    public function update_1()
    {
        $field = '<field>
                    <name>deadline</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>false</notnull>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setTableVersion('timetracker_timeaccount', '6');

        $this->setApplicationVersion('Timetracker', '3.0');
    }
}
