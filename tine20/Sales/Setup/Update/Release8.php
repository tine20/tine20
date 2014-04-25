<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class Sales_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * @see: 0009048: sometimes the status of sales contract has an icon, sometimes not
     *       https://forge.tine20.org/mantisbt/view.php?id=9048
     */
    public function update_0()
    {
        $quotedTableName = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "sales_contracts");
        
        $sql = "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('status') . " = 'OPEN' WHERE " . $this->_db->quoteIdentifier('status') . " = 'open';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('status') . " = 'CLOSED' WHERE " . $this->_db->quoteIdentifier('status') . " = 'closed';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'CLEARED' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'cleared';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'TO_CLEAR' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'to clear';";
        $this->_db->query($sql);
        $sql =  "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('cleared') . " = 'NOT_YET_CLEARED' WHERE " . $this->_db->quoteIdentifier('cleared') . " = 'not yet cleared';";
        $this->_db->query($sql);
        
        $this->setApplicationVersion('Sales', '8.1');
    }
    
    /**
     * update to 8.1
     * - update 256 char fields
     * 
     * @see 0008070: check index lengths
     */
    public function update_1()
    {
        $columns = array("sales_contracts" => array(
                    "title" => "true",
                    "cleared_in" => "false"
                    )
                );
        
        $this->truncateTextColumn($columns, 255);
        $this->setTableVersion('sales_contracts', 6);
        $this->setApplicationVersion('Sales', '8.2');
    }

    /**
     * update to 8.3
     *   - add modlog to costcenter model
     */
    public function update_2()
    {
        $fields = array('<field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field> ','
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>','
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>seq</name>
                <type>integer</type>
                <notnull>true</notnull>
                <default>0</default>
            </field>');
        
        foreach($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_cost_centers', $declaration);
        }
        
        $this->setTableVersion('sales_cost_centers', 2);;
        $this->setApplicationVersion('Sales', '8.3');
    }
    
    /**
     * update to 8.4
     */
    public function update_3()
    {
        $this->_backend->dropIndex('sales_numbers', 'type');
    
        $field = '<field>
            <name>model</name>
            <type>text</type>
            <length>128</length>
            <notnull>true</notnull>
        </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('sales_numbers', $declaration, 'type');
    
        $index = '<index>
            <name>model</name>
            <unique>model</unique>
            <field>
                <name>model</name>
            </field>
        </index>';
    
        $declaration = new Setup_Backend_Schema_Index_Xml($index);
        $this->_backend->addIndex('sales_numbers', $declaration);
    
        $db = $this->_backend->getDb();
    
        $sql = 'UPDATE ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'sales_numbers') . ' SET ' . $db->quoteInto($db->quoteIdentifier('model') . ' = ?', 'Sales_Model_Contract') . ' WHERE ' . $db->quoteInto($db->quoteIdentifier('model') . ' = ?', 'contract');
        $db->query($sql);
    
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Sales'));
    
        $this->setTableVersion('sales_numbers', 2);
        $this->setApplicationVersion('Sales', '8.4');
    }
    
    /**
     * creates the customer and related address table
     */
    protected function _createCustomerAndAddressTables()
    {
        // create customer table
        $tableDefinition = '<table>
            <name>sales_customers</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>number</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                    <length>32</length>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>cpextern_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>cpintern_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>vatid</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>url</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>iban</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>bic</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>credit_term</name>
                    <type>integer</type>
                    <notnull>false</notnull>
                    <length>10</length>
                </field>
                <field>
                    <name>currency</name>
                    <type>text</type>
                    <notnull>false</notnull>
                    <length>4</length>
                </field>
                <field>
                    <name>currency_trans_rate</name>
                    <type>float</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>discount</name>
                    <type>float</type>
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
            </declaration>
        </table>';
    
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
    
        // create addresses table
        $tableDefinition = '<table>
            <name>sales_addresses</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>customer_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>text</type>
                    <notnull>true</notnull>
                    <default>postal</default>
                    <length>64</length>
                </field>
                <field>
                    <name>prefix1</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>prefix2</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>street</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>postalcode</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>locality</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>region</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>countryname</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>pobox</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>custom1</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>';
    
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
    
        // create one persistent filter (looks better)
    
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
    
        $pfe->create(new Tinebase_Model_PersistentFilter(
            array(
                'account_id'        => NULL,
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
                'model' => 'Sales_Model_CustomerFilter',
                'name'        => "All Customers", // _('All Customers')
                'description' => "All customer records", // _('All customer records')
                'filters'     => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            )
        ));
    }
    
    /**
     * adds modlog to sales-divisions
     */
    protected function _addDivisionsModlog()
    {
        $fields = array('<field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field> ','
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>','
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>seq</name>
                <type>integer</type>
                <notnull>true</notnull>
                <default>0</default>
            </field>');
        
        foreach ($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            try {
                $this->_backend->addCol('sales_divisions', $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Exception::log($zdse);
            }
        }
        $this->setTableVersion('sales_divisions', 2);
    }
    
    /**
     * creates invoice table and keyfields, config and so on
     */
    protected function _createInvoiceTableAndRelated()
    {
        // create invoice table
        $tableDefinition = '<table>
            <name>sales_invoices</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>number</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>address_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>fixed_address</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>is_auto</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>
                <field>
                    <name>date</name>
                    <type>date</type>
                </field>
                <field>
                    <name>start_date</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>end_date</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>credit_term</name>
                    <type>integer</type>
                    <notnull>false</notnull>
                    <length>10</length>
                </field>
                <field>
                    <name>costcenter_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cleared</name>
                    <type>text</type>
                    <length>64</length>
                    <default>TO_CLEAR</default>
                </field>
                <field>
                    <name>type</name>
                    <type>text</type>
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
            </declaration>
        </table>';
    
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
    
        // create keyfield config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
    
        $tc = array(
            'name'    => Sales_Config::INVOICE_TYPE,
            'records' => array(
                array('id' => 'INVOICE',  'value' => 'invoice',   'system' => TRUE),
                array('id' => 'REVERSAL', 'value' => 'reversal',  'system' => TRUE),
                array('id' => 'CREDIT',   'value' => 'credit',    'system' => TRUE)
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::INVOICE_TYPE,
            'value'             => json_encode($tc),
        )));
    
        // create cleared state keyfields
        $tc = array(
            'name'    => Sales_Config::INVOICE_CLEARED,
            'records' => array(
                array('id' => 'TO_CLEAR',  'value' => 'to clear',   'system' => TRUE),
                array('id' => 'CLEARED', 'value' => 'cleared',  'system' => TRUE),
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::INVOICE_CLEARED,
            'value'             => json_encode($tc),
        )));
    }
    
    /**
     * adds invoice related fields to contract
     */
    protected function _addInvoiceFieldsToContract()
    {
        // add new sales contract fields
        $fields = array('<field>
            <name>billing_address_id</name>
            <type>text</type>
            <length>40</length>
            <notnull>false</notnull>
        </field>','
        <field>
            <name>last_autobill</name>
            <type>datetime</type>
            <notnull>false</notnull>
            <default>null</default>
        </field>','
        <field>
            <name>interval</name>
            <type>integer</type>
            <default>1</default>
        </field>','
        <field>
            <name>billing_point</name>
            <type>text</type>
            <length>64</length>
            <notnull>false</notnull>
        </field>'
        );
    
        foreach($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_contracts', $declaration);
        }
    
        if ($this->getTableVersion('sales_contracts') == 5) {
            $this->setTableVersion('sales_contracts', 6);
        } else {
            $this->setTableVersion('sales_contracts', 7);
        }
    }
    
    /**
     * adds modlog to addresses to prevent data loss
     */
    protected function _addModlogToAddresses()
    {
        $fields = array('<field>
                    <name>start_date</name>
                    <type>datetime</type>
                </field>','
                <field>
                    <name>end_date</name>
                    <type>datetime</type>
                </field>','
                <field>
                    <name>created_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>','
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>','
                <field>
                    <name>last_modified_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>','
                <field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>','
                <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>','
                <field>
                    <name>deleted_by</name>
                    <type>text</type>
                    <length>40</length>
                </field>','
                <field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                </field>','
                <field>
                    <name>seq</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>0</default>
                </field>');
    
        foreach($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_addresses', $declaration);
        }
    
        $this->setTableVersion('sales_addresses', 2);
    }
    
    /**
     * adds "start_date", "end_date" to contract and removes "status", "cleared", "cleared_in"
     */
    protected function _updateContractsFields()
    {
        if (php_sapi_name() == 'cli') {
            echo 'The Users\' locale you use here will be used to translate the fields "status", "cleared", "cleared_in":';
        }
        
        $this->promptForUsername();
        
        $controller = Sales_Controller_Contract::getInstance();
        
        $table = new Zend_Db_Table(SQL_TABLE_PREFIX . 'sales_contracts', new Zend_Db_Table_Definition(array(
            'id' => array('name' => 'id'),
            'status' => array('name' => 'status'),
            'cleared' => array('name' => 'cleared'),
            'cleared_in' => array('name' => 'cleared_in'),
            'description' => array('name' => 'description'),
            'last_modified_time' => array('name' => 'last_modified_time')
        )));
        
        $count = 50;
        $offset = 0;
        $more = true;
        $updateDescription = $statusConfig = $clearedConfig = $setEndDate = array();
        
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId();
        $pref = Tinebase_Core::getPreference('Tinebase');
        Tinebase_Core::setupUserLocale($pref->locale);
        $t = Tinebase_Translation::getTranslation('Sales', Tinebase_Core::getLocale());
        
        $config = Sales_Config::getInstance()->get('contractStatus');
        foreach($config['records'] as $cfg) {
            $statusConfig[$cfg['id']] = $cfg['value'];
        }
        
        $config = Sales_Config::getInstance()->get('contractCleared');
        
        foreach($config['records'] as $cfg) {
            $clearedConfig[$cfg['id']] = $cfg['value'];
        }
        
        while($more) {
            $results = $table->fetchAll(NULL, NULL, $count, $offset)->toArray();
            foreach ($results as $row) {
        
                if ($row['status'] == 'CLOSED') {
                    $setEndDate[$row['id']] = $row['last_modified_time'];
                }
        
        
                $desc = $row['description'];
                $desc .= PHP_EOL . '---' . PHP_EOL . PHP_EOL;
                $contents = FALSE;
        
                if (! empty($row['status'])) {
                    $desc .= $t->_('Status') . ': ';
                    $desc .= (isset($statusConfig[$row['status']]) ? $t->_($statusConfig[$row['status']]) : $row['status']);
                    $desc .= PHP_EOL;
                    $contents = TRUE;
                }
                if (! empty($row['cleared'])) {
                    $desc .= $t->_('Cleared') . ': ';
                    $desc .= (isset($clearedConfig[$row['cleared']]) ? $t->_($clearedConfig[$row['cleared']]) : $row['cleared']);
                    $desc .= PHP_EOL;
                    $contents = TRUE;
                }
                if (! empty($row['cleared_in'])) {
                    $desc .= $t->_('Cleared In') . ': ';
                    $desc .= $row['cleared_in'];
                    $desc .= PHP_EOL;
                    $contents = TRUE;
                }
        
                if ($contents) {
                    $updateDescription[$row['id']] = $desc . PHP_EOL;
                }
            }
        
            if (count($updateDescription) > 50) {
                foreach($controller->getMultiple(array_keys($updateDescription)) as $contr) {
                    $contr->description = $updateDescription[$contr->getId()];
                    $controller->update($contr, FALSE);
                }
                $updateDescription = array();
            }
        
            if (count($results) < $count) {
                $more = FALSE;
            } else {
                $offset = $offset + $count;
            }
        }
        
        foreach($controller->getMultiple(array_keys($updateDescription)) as $contr) {
            $contr->description = $updateDescription[$contr->getId()];
            $controller->update($contr, FALSE);
        }
        
        // remove deprecated sales contract fields
        foreach (array('status', 'cleared_in', 'cleared') as $colToDrop) {
            try {
                $this->_backend->dropCol('sales_contracts', $colToDrop);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Exception::log($zdse);
            }
        }
        
        // add new sales contract fields
        $fields = array('<field>
            <name>start_date</name>
            <type>datetime</type>
        </field>','
        <field>
            <name>end_date</name>
            <type>datetime</type>
        </field>'
        );
        
        foreach($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_contracts', $declaration);
        }
        
        $table = new Zend_Db_Table(SQL_TABLE_PREFIX . 'sales_contracts', new Zend_Db_Table_Definition(array(
            'id' => array('name' => 'id'),
            'last_modified_time' => array('name' => 'last_modified_time'),
            'end_date' => array('name' => 'end_date'),
            'start_date' => array('name' => 'start_date'),
        )));
        
        
        $db = $table->getAdapter();
        $values = array_keys($setEndDate);
        if (! empty($values)) {
            $sql = 'UPDATE ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'sales_contracts') . ' SET ' .
                $db->quoteIdentifier('start_date') . ' = ' . $db->quoteIdentifier('last_modified_time') . ', '.
                $db->quoteIdentifier('end_date') . ' = ' . $db->quoteIdentifier('last_modified_time') .
                ' WHERE ' . $db->quoteIdentifier('id') . $db->quoteInto(' IN (?)', $values);
            
            $db->query($sql);
        }
        
        if ($this->getTableVersion('sales_contracts') == 5) {
            $this->setTableVersion('sales_contracts', 6);
        } else {
            $this->setTableVersion('sales_contracts', 7);
        }
    }
    
    /**
     * update to 8.5
     *
     * switch to modelconfig, create divison module
     * remove "status", "cleared", "cleared_in", but append values to the description field
     */
    public function update_4()
    {
        // repeat from update_3 if setup has been run on another branch
        if (! $this->_backend->tableExists('sales_customers')) {
            $this->_createCustomerAndAddressTables();
        }
        
        if (! $this->_backend->columnExists('seq', 'sales_divisions')) {
            $this->_addDivisionsModlog();
        }
    
        $this->setApplicationVersion('Sales', '8.5');
    }
    
    /**
     * update to 8.6
     * 
     * drop number index of cost center table
     */
    public function update_5()
    {
        $this->_backend->dropIndex('sales_cost_centers', 'number');
        $this->setTableVersion('sales_cost_centers', 3);
        $this->setApplicationVersion('Sales', '8.6');
    }
    
    /**
     * update to 8.7
     * 
     *  - add invoice module
     */
    public function update_6()
    {
        // repeat from update_3 if setup has been run on another branch
        if (! $this->_backend->tableExists('sales_customers')) {
            $this->_createCustomerAndAddressTables();
        }
        
        // repeat from update_4 if setup has been run on another branch
        if (! $this->_backend->columnExists('seq', 'sales_divisions')) {
            $this->_addDivisionsModlog();
        }
        // repeat from update_4 if setup has been run on another branch
        if (! $this->_backend->columnExists('start_date', 'sales_contracts')) {
            $this->_updateContractsFields();
        }
        
        if (! $this->_backend->tableExists('sales_invoices')) {
            $this->_createInvoiceTableAndRelated();
        }
        
        if (! $this->_backend->columnExists('billing_point', 'sales_contracts')) {
            $this->_addInvoiceFieldsToContract();
        }
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Sales'));

        $this->setApplicationVersion('Sales', '8.7');
    }
    
    /**
     * update to 8.8
     * 
     *  - add modlog to addresses
     */
    public function update_7()
    {
        $this->_addModlogToAddresses();
        $this->setApplicationVersion('Sales', '8.8');
    }
    
    /**
     * update to 8.9
     * 
     *  - add order confirmation module
     */
    public function update_8()
    {
        $tableDefinition = '<table>
            <name>sales_orderconf</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>title</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>number</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
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
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        
        $this->setApplicationVersion('Sales', '8.9');
    }
    
    /**
     * update to 8.9
     *
     *  - add products to contracts
     */
    public function update_9()
    {
        $this->_createProductAggregateTable();
    
        $this->setApplicationVersion('Sales', '8.10');
    }
    
    /**
     * creates the aggregate table for contract->products
     */
    protected function _createProductAggregateTable()
    {
        $table = '<table>
            <name>sales_product_agg</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>product_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>contract_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>quantity</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>1</default>
                </field>
                <field>
                    <name>interval</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>1</default>
                </field>
                <field>
                    <name>last_autobill</name>
                    <type>datetime</type>
                    <notnull>false</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>product_id</name>
                    <field>
                        <name>product_id</name>
                    </field>
                </index>
                <index>
                    <name>contract_id</name>
                    <field>
                        <name>contract_id</name>
                    </field>
                </index>
            </declaration>
        </table>';
    
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $table);
        $this->_backend->createTable($table);
    }
    
    public function update_10()
    {
        $declaration = '<table>
            <name>sales_invoice_positions</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>invoice_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>accountable_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>model</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>title</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>month</name>
                    <type>text</type>
                    <length>7</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>unit</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>quantity</name>
                    <type>integer</type>
                    <notnull>true</notnull>
                    <default>1</default>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>invoice_id</name>
                    <field>
                        <name>invoice_id</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $declaration);
        $this->_backend->createTable($table);
        
        $this->setApplicationVersion('Sales', '8.11');
    }
}
