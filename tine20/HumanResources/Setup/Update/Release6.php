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
     * update to 6.7
     * 
     * - remove costcenter from contract, create costcenter-employee-mm table
     */
    public function update_6()
    {
        $table = new Setup_Backend_Schema_Table_Xml('<table>
            <name>humanresources_costcenter</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>start_date</name>
                    <type>datetime</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>employee_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cost_center_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
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
            </declaration>
        </table>');
        
        $this->_backend->createTable($table);
        
        // find all contracts
        $select = $this->_db->select()->from( SQL_TABLE_PREFIX . 'humanresources_contract')->where('is_deleted=0');
        $stmt = $select->query();
        $contracts = $stmt->fetchAll();
        
        $now = new Tinebase_DateTime();
        $be = HumanResources_Controller_CostCenter::getInstance();
        
        foreach($contracts as $contract) {
            if($contract['cost_center_id']) {
                $costcenter = new HumanResources_Model_CostCenter(array(
                    'employee_id'    => $contract['employee_id'],
                    'cost_center_id' => $contract['cost_center_id'],
                    'start_date'     => $contract['start_date'] ? $contract['start_date'] : (string) $now
                ));
                $be->create($costcenter);
            }
        }
        // remove costcenter property from contract
        try {
            $this->_backend->dropCol('humanresources_contract', 'cost_center_id');
        } catch (Exception $e) {
            
        }
        
        // create type config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId();
        
        // update vacation status config
        $kfc = $cb->getByProperty('freetimeStatus');
        $kfc->name = HumanResources_Config::VACATION_STATUS;
        $cb->update($kfc);
        
        // create sickness status config
        $sicknessStatusConfig = array(
            'name'    => HumanResources_Config::SICKNESS_STATUS,
            'records' => array(
                array('id' => 'EXCUSED',   'value' => 'Excused',   'icon' => 'images/oxygen/16x16/actions/smiley.png', 'system' => true),  //_('Excused')
                array('id' => 'UNEXCUSED', 'value' => 'Unexcused', 'icon' => 'images/oxygen/16x16/actions/tools-report-bug.png', 'system' => true),  //_('Unexcused')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::SICKNESS_STATUS,
            'value'             => json_encode($sicknessStatusConfig),
        )));
        
        // update sickness records, set status = excused
        $filter = new HumanResources_Model_FreeTimeFilter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => 'SICKNESS')
            ));
        
        $ftb = new HumanResources_Backend_FreeTime();
        $records = $ftb->search($filter);
        $ftb->updateMultiple($records->id, array('status' => 'EXCUSED'));
        
        // create persistenfilters
        
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Currently employed", // _("Currently employed")
            'description'       => "Employees which are currently employed", // _("Employees which are currently employed")
            'filters'           => array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => 1)),
        ))));
        
        // add workingtime json
        $field = '<field>
            <name>workingtime_json</name>
            <type>text</type>
            <length>1024</length>
            <notnull>true</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('humanresources_contract', $declaration);
        $this->setTableVersion('humanresources_contract', '4');
        
        // change freetime type field length
        $field = '<field>
                    <name>type</name>
                    <type>text</type>
                    <length>64</length>
                    <default>vacation</default>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('humanresources_freetime', $declaration);
        $this->setTableVersion('humanresources_freetime', '3');
        
        // add vacation types
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
            'modlogActive' => false
        ));
    
        $appId = Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId();
        $filter = new Tinebase_Model_ConfigFilter(array(array('field' => 'name', 'operator' => 'equals', 'value' => HumanResources_Config::FREETIME_TYPE)));
        $ftt = $cb->search($filter)->getFirstRecord();
        
        $val = json_decode($ftt->value);
        
        $existing = $val->records;
        
        $existing[] = array('id' => 'VACATION_REMAINING',   'value' => 'Remaining Vacation', 'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true);
        $existing[] = array('id' => 'VACATION_EXTRA',       'value' => 'Extra Vacation',     'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true);
        
        $freeTimeTypeConfig = array(
            'name'    => HumanResources_Config::FREETIME_TYPE,
            'records' => $existing,
        );

        $ftt->value = json_encode($freeTimeTypeConfig);
        $cb->update($ftt);
        
        // update json of workingtime models if they still exist
        
        $controller = HumanResources_Controller_WorkingTime::getInstance();
        $controller->modlogActive(false);
        
        $filter = new HumanResources_Model_WorkingTimeFilter(array());//array('field' => 'working_hours', 'operator' => 'equals', 'value' => '40')));
        $allWT = $controller->search($filter);
        
        $wt40 = $allWT->filter('working_hours', "40");
        foreach($wt40 as $wt) {
            $wt->json = '{"days":[8,8,8,8,8,0,0]}';
            $controller->update($wt);
        }
        
        $wt37 = $allWT->filter('working_hours', "37.5");
        foreach($wt37 as $wt) {
            $wt->json = '{"days":[8,8,8,8,5.5,0,0]}';
            $controller->update($wt);
        }
        
        $wt20 = $allWT->filter('working_hours', "20");
        foreach($wt20 as $wt) {
            $wt->json = '{"days":[4,4,4,4,4,0,0]}';
            $controller->update($wt);
        }
        
        $this->setApplicationVersion('HumanResources', '6.7');
    }
    
    /**
     * update 7
     * 
     * - remove foreign keys for employee-account_id, -supervisor_id
     * - make supervisor_id hold an employee, not an account
     * 
     * @see 0007666: can't delete user that is linked to an employee
     */
    public function update_7($requiredTableVersion = 6)
    {
        if ($this->getTableVersion('humanresources_employee') == $requiredTableVersion) {
            try {
                $this->_backend->dropForeignKey('humanresources_employee', 'hr_employee::account_id--accounts::id');
            } catch (Zend_Db_Statement_Exception $e) {
                
            }
            
            try {
                $this->_backend->dropForeignKey('humanresources_employee', 'hr_employee::supervisor_id--accounts::id');
            } catch (Zend_Db_Statement_Exception $e) {
                
            }
            
            $c = new HumanResources_Backend_Employee();
            $f = new HumanResources_Model_EmployeeFilter();
            
            $allEmployees = $c->search($f);
            $employees = clone $allEmployees;
            
            foreach ($employees as $employee) {
                $linkEmployee = $allEmployees->filter('account_id', $employee->supervisor_id)->getFirstRecord();
                if ($linkEmployee) {
                    $employee->supervisor_id = $linkEmployee->getId();
                    $c->update($employee);
                }
            }
            
            $this->setTableVersion('humanresources_employee', '7');
        }
        $this->setApplicationVersion('HumanResources', '6.8');
    }
    
    /**
     * update to 7.0
     */
    public function update_8()
    {
        $this->setApplicationVersion('HumanResources', '7.0');
    }
}
