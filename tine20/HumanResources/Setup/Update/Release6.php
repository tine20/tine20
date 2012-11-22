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
    
    /**
     * update 6.3 -> 6.4
     * - add fields title, salutation, n_family, n_given
     */
    public function update_3()
    {
        $fields = array('<field>
                    <name>title</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>', '
                <field>
                    <name>salutation</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>', '
                <field>
                    <name>n_family</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>', '
                <field>
                    <name>n_given</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>');

        foreach ($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('humanresources_employee', $declaration);
        }
        $this->setTableVersion('humanresources_employee', '4');
        $this->setApplicationVersion('HumanResources', '6.4');
    }
    /**
     * update 6.4 -> 6.5
     * - add field profession
     */
    public function update_4()
    {
        $field = '<field>
                    <name>profession</name>
                    <type>text</type>
                    <length>128</length>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_employee', $declaration);
        
        $this->setTableVersion('humanresources_employee', '5');
        $this->setApplicationVersion('HumanResources', '6.5');
    }
    
    /**
     * update to 6.6
     * 
     * - add index to contracts table
     *   @see 0007474: add primary key to humanresources_contract
     * - remove sales constrains
     * 
     */
    public function update_5()
    {
        $field = '<index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>';

        $declaration = new Setup_Backend_Schema_Index_Xml($field);
        try {
            $this->_backend->addIndex('humanresources_contract', $declaration);
        } catch (Zend_Db_Statement_Exception $e) {
            
        }
        try {
            $this->_backend->dropForeignKey('humanresources_contract', 'contract::cost_center_id--sales_cost_centers::id');
        } catch (Zend_Db_Statement_Exception $e) {
            
        }
        try {
            $this->_backend->dropIndex('humanresources_contract', 'contract::cost_center_id--sales_cost_centers::id');
        } catch (Zend_Db_Statement_Exception $e) {
            
        }
        try {
            $this->_backend->dropForeignKey('humanresources_employee', 'hr_employee::division_id--divisions::id');
        } catch (Zend_Db_Statement_Exception $e) {
            
        }
        try {
            $this->_backend->dropIndex('humanresources_employee', 'hr_employee::division_id--divisions::id');
        } catch (Zend_Db_Statement_Exception $e) {
            
        }
        $this->setTableVersion('humanresources_employee', '6');
        $this->setTableVersion('humanresources_contract', '3');
        $this->setApplicationVersion('HumanResources', '6.6');
    }
    
    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_6()
    {
        $this->setApplicationVersion('HumanResources', '7.0');
    }
}
