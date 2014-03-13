<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * update to 8.2
     *   - add modlog to costcenter model
     */
    public function update_1()
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
        
        $this->setTableVersion('sales_cost_centers', 2);
        $this->setApplicationVersion('Sales', '8.2');
    }
    
    /**
     * update to 8.3
     */
    public function update_2()
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
        $this->setApplicationVersion('Sales', '8.3');
    }
    
    /**
     * adds "start_date", "end_date" to contract and removes "status", "cleared", "cleared_in"
     */
    protected function _updateContractsFields()
    {
        if (php_sapi_name() == 'cli') {
            echo 'The Users\' locale you use here will be used to translate the fields "status", "cleared", "cleared_in":';
        }
        
        $controller = NULL;
        
        // cleared, cleared_in, status gets deleted, if the update is not called on cli
        try {
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
        } catch (Setup_Exception_PromptUser $e) {
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
                $db->quoteIdentifier('start_date') . ' = ' . $db->quoteIdentifier('creation_time') . ', '.
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
     * adds modlog to sales-divisions
     */
    protected function _addDivisionsModlog() {
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
     * update to 8.4
     * 
     * switch to modelconfig, create divison module
     * remove "status", "cleared", "cleared_in", but append values to the description field
     */
    public function update_3()
    {
        if (! $this->_backend->columnExists('seq', 'sales_divisions')) {
            $this->_addDivisionsModlog();
        }
        
        if (! $this->_backend->columnExists('start_date', 'sales_contracts')) {
            $this->_updateContractsFields();
        }
        
        $this->setApplicationVersion('Sales', '8.4');
    }
}
