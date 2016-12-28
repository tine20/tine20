<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class HumanResources_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * 
     *  - Extrafreetime days can be negative
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>days</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <unsigned>false</unsigned>
                    <length>4</length>
                </field>');
        $this->_backend->alterCol('humanresources_extrafreetime', $declaration);

        $this->setTableVersion('humanresources_extrafreetime', '3');
        $this->setApplicationVersion('HumanResources', '9.1');
    }

    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('HumanResources', '10.0');
    }
}
