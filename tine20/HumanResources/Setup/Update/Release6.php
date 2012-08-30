<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class HumanResources_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update 6.0 -> 6.1
     * - account_id can be NULL
     */
    public function update_0()
    {
        $field = '<field>
            <name>account_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>';

        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_employee', $declaration);
        $this->setTableVersion('humanresources_employee', '2');
        $this->setApplicationVersion('HumanResources', '6.1');
    }

    /**
     * update 6.1 -> 6.2
     * - number should be int(11)
     */
    public function update_1()
    {
        $field = '<field>
                    <name>number</name>
                    <type>integer</type>
                    <notnull>false</notnull>
                </field>';

        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_employee', $declaration);
        $this->setTableVersion('humanresources_employee', '3');
        $this->setApplicationVersion('HumanResources', '6.2');
    }

    /**
     * update 6.2 -> 6.3
     * - some cols can be NULL
     */
    public function update_2()
    {
        $fields = array('<field>
                    <name>firstday_date</name>
                    <type>date</type>
                </field>', '<field>
                    <name>remark</name>
                    <type>text</type>
                    <length>255</length>
                </field>', );

        foreach ($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->alterCol('humanresources_freetime', $declaration);
        }
        $this->setTableVersion('humanresources_freetime', '2');
        $this->setApplicationVersion('HumanResources', '6.3');
    }
}
