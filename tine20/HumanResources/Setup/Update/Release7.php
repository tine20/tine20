<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class HumanResources_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $seqModels = array(
            'HumanResources_Model_Contract'     => array('name' => 'humanresources_contract',    'version' => 3),
            'HumanResources_Model_Employee'     => array('name' => 'humanresources_employee',    'version' => 6),
            'HumanResources_Model_FreeTime'     => array('name' => 'humanresources_freetime',    'version' => 3),
            'HumanResources_Model_WorkingTimeScheme' => array('name' => 'humanresources_workingtime', 'version' => 2),
        );
        
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        foreach ($seqModels as $model => $tableInfo) {
            try {
                $this->_backend->addCol($tableInfo['name'], $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                // ignore
            }
            $this->setTableVersion($tableInfo['name'], $tableInfo['version']);
            Tinebase_Setup_Update_Release7::updateModlogSeq($model, $tableInfo['name']);
        }
        
        $this->setApplicationVersion('HumanResources', '7.1');
    }
    
    /**
     * update 7.1 -> 7.2
     * 
     * - add index to contracts table
     *   @see 0007474: add primary key to humanresources_contract
     * - remove sales constrains
     */
    public function update_1()
    {
        $update6 = new HumanResources_Setup_Update_Release6($this->_backend);
        $update6->update_5();
        
        $this->setTableVersion('humanresources_employee', '7');
        $this->setTableVersion('humanresources_contract', '4');
        $this->setApplicationVersion('HumanResources', '7.2');
    }

    /**
     * update 7.2 -> 7.3
     * 
     * - remove costcenter from contract, create costcenter-employee-mm table
     */
    public function update_2()
    {
        $update6 = new HumanResources_Setup_Update_Release6($this->_backend);
        if ($this->getTableVersion('humanresources_costcenter') === 0) {
            $update6->update_6();
            $this->setTableVersion('humanresources_contract', '5');
            $this->setTableVersion('humanresources_freetime', '4');
        }
        $this->setApplicationVersion('HumanResources', '7.3');
    }

    /**
     * update 7.3 -> 7.4
     * 
     * - remove foreign keys for employee-account_id, -supervisor_id
     * - make supervisor_id hold an employee, not an account
     * 
     * @see 0007666: can't delete user that is linked to an employee
     */
    public function update_3()
    {
        $update6 = new HumanResources_Setup_Update_Release6($this->_backend);
        $update6->update_7(7);
        $this->setTableVersion('humanresources_employee', '8');
        $this->setApplicationVersion('HumanResources', '7.4');
    }

    /**
     * update 7.4 -> 7.5
     *
     * @see #7924: HR Modul - Description text not saved
     * - rename field freetime.remark to freetime.description
     * - add field employee.description
     */
    public function update_4()
    {
        $update6 = new HumanResources_Setup_Update_Release6($this->_backend);
        $update6->update_8();
        $this->setTableVersion('humanresources_employee', '9');
        $this->setTableVersion('humanresources_freetime', '5');
        $this->setApplicationVersion('HumanResources', '7.5');
    }
    
    /**
     * update 7.5 -> 7.6
     *
     * @see #7924: HR Modul - Description text not saved
     * - rename field freetime.remark to freetime.description
     * - add field employee.description
     */
    public function update_5()
    {
        $update6 = new HumanResources_Setup_Update_Release6($this->_backend);
        $update6->update_9();
        $this->setTableVersion('humanresources_employee', '10');
        $this->setApplicationVersion('HumanResources', '7.6');
    }
    
    /**
     * update 7.6 -> 7.7
     *
     * - remove foreign key for contract.feast calendar
     * - add "all employees" persistentfilter
     * - update "employed employees filter"
     */
    public function update_6()
    {
        try {
            $this->_backend->dropForeignKey('humanresources_contract', 'contract::feast_calendar_id--container::id');
        } catch (Zend_Db_Statement_Exception $e) {
        
        }
        
        // add persistentfilter all employees
        $pfe = Tinebase_PersistentFilter::getInstance();
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
            'name'              => "All employees", // _("All employees")
            'description'       => "All available employees", // _("All available employees")
            'filters'           => array(),
        )));

        $filter = new Tinebase_Model_PersistentFilterFilter(array(array('field' => 'name', 'operator' => 'equals', 'value' => 'Currently employed employee')));
        $result = $pfe->search($filter);
        $record = $result->getFirstRecord();
        
        if ($record) {
            $record->name = 'Currently employed employees';
            $record->description = 'Employees which are currently employed';
            $pfe->update($record);
        }
        
        $this->setTableVersion('humanresources_contract', '2');
        $this->setApplicationVersion('HumanResources', '7.7');
    }

    /**
     * update 7.7 -> 7.8
     * 
     * - change description field varchar 255 -> clob
     * 
     * @see https://forge.tine20.org/mantisbt/view.php?id=7994
     */
    public function update_7()
    {
        $field = '<field>
                    <name>description</name>
                    <type>clob</type>
                </field>';

        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_employee', $declaration);
        $this->setTableVersion('humanresources_employee', '12');
        $this->setApplicationVersion('HumanResources', '7.8');
    }
    
    /**
     * update 7.8 -> 7.9
     *
     * - add account module with the corresponding tables
     */
    public function update_8()
    {
        $tableDeclaration = new Setup_Backend_Schema_Table_Xml('
            <table>
            <name>humanresources_account</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>employee_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>year</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <length>4</length>
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
                <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>account::employee_id--employee::id</name>
                    <field>
                        <name>employee_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>humanresources_employee</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>
        ');
        $this->_backend->createTable($tableDeclaration, 'HumanResources');
        $tableDeclaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>humanresources_extrafreetime</name>
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
                        <name>days</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                        <length>4</length>
                    </field>
                    <field>
                        <name>type</name>
                        <type>text</type>
                        <length>64</length>
                        <default>vacation</default>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>255</length>
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
                    <field>
                        <name>seq</name>
                        <type>integer</type>
                        <notnull>true</notnull>
                        <default>0</default>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                    <index>
                        <name>exfreetime::account_id--account::id</name>
                        <field>
                            <name>account_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>humanresources_account</table>
                            <field>id</field>
                        </reference>
                    </index>
                </declaration>
            </table>
        ');
        
        $this->_backend->createTable($tableDeclaration, 'HumanResources');
        
        // extra free time type
        $freeTimeTypeConfig = array(
            'name'    => HumanResources_Config::EXTRA_FREETIME_TYPE,
            'records' => array(
                array('id' => 'PAYED',     'value' => 'Payed',     'icon' => NULL, 'system' => TRUE),  //_('Payed')
                array('id' => 'NOT_PAYED', 'value' => 'Not payed', 'icon' => NULL, 'system' => TRUE),  //_('Not payed')
            ),
        );
        
        // create type config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId();
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::EXTRA_FREETIME_TYPE,
            'value'             => json_encode($freeTimeTypeConfig),
        )));
        
        // remove unused stati
        $filter = new Tinebase_Model_ConfigFilter(array(
            array('field' => 'name', 'operator' => 'equals', 'value' => HumanResources_Config::FREETIME_TYPE)
        ));
        $record = $cb->search($filter)->getFirstRecord();
        $result = json_decode($record->value);
        $newResult = array('name' => HumanResources_Config::FREETIME_TYPE);
        foreach($result->records as $field) {
            if ($field->id == 'VACATION_EXTRA' || $field->id == 'VACATION_REMAINING') {
                continue;
            }
            $newResult['records'][] = $field;
        }
        $record->value = json_encode($newResult);
        $cb->update($record);
        
        $this->setApplicationVersion('HumanResources', '7.9');
    }
    
    /**
     * update 7.9 -> 7.10
     *
     * - fix account-year field
     *   https://forge.tine20.org/mantisbt/view.php?id=8140
     */
    public function update_9()
    {
        $field = '<field>
            <name>year</name>
            <type>integer</type>
            <notnull>true</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_account', $declaration);
        $this->setTableVersion('humanresources_account', '2');
        $this->setApplicationVersion('HumanResources', '7.10');
    }
    

    /**
     * update 7.10 -> 7.11
     *
     * - Update import / export definitions
     */
    public function update_10()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('HumanResources'));
        $this->setApplicationVersion('HumanResources', '7.11');
    }
    
    /**
     * update 7.11 -> 7.12
     *
     * - add expiration date to extra freetimes
     */
    public function update_11()
    {
        if ((! $this->_backend->tableExists('humanresources_extrafreetime')) && $this->_backend->tableExists('humanresources_extra_freetime')) {
            $this->_backend->renameTable('humanresources_extra_freetime', 'humanresources_extrafreetime');
        }
        
        $field = '<field>
                    <name>expires</name>
                    <type>datetime</type>
                    <notnull>false</notnull>
                </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        try {
            $this->_backend->addCol('humanresources_extrafreetime', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_Exception::log($zdse);
        }
        $this->setTableVersion('humanresources_extrafreetime', '2');
        
        $field = '<field>
                    <name>date</name>
                    <type>date</type>
                    <notnull>false</notnull>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_freeday', $declaration);
        $this->setTableVersion('humanresources_freeday', '2');
        
        $field = '<field>
            <name>account_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_freetime', $declaration);
        $this->setTableVersion('humanresources_freetime', '6');
        
        $this->setApplicationVersion('HumanResources', '7.12');
    }
    
    /**
     * update 7.12 -> 7.13
     *
     * - create accounts for this and next year
     */
    public function update_12()
    {
        $date = Tinebase_DateTime::now();
        HumanResources_Controller_Account::getInstance()->createMissingAccounts((int) $date->format('Y'), NULL, TRUE);
        
        $date->addYear(1);
        HumanResources_Controller_Account::getInstance()->createMissingAccounts((int) $date->format('Y'), NULL, TRUE);
        
        $this->setApplicationVersion('HumanResources', '7.13');
    }

    /**
     * update 7.13 -> 7.14
     *
     * - add iban and bic to employee model
     */
    public function update_13()
    {
        $field = '<field>
                    <name>bic</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>false</notnull>
                </field>
                ';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_employee', $declaration);
        
        $field = '<field>
                    <name>iban</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>false</notnull>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_employee', $declaration);
        $this->setTableVersion('humanresources_employee', '13');
        
        $this->setApplicationVersion('HumanResources', '7.14');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_14()
    {
        $this->setApplicationVersion('HumanResources', '8.0');
    }
}
