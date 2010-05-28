<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Sales_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * renamed erp to sales
     * 
     */
    public function update_0()
    {
        $this->renameTable('erp_numbers', 'sales_numbers');
        $this->renameTable('erp_contracts', 'sales_contracts');
        
        $this->setApplicationVersion('Sales', '2.1');
    }

    /**
     * - create sales product table
     * - copy products from metacrm_products to new table
     * 
     */
    public function update_1()
    {
        $tableDefinition = '
            <table>
            <name>sales_products</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>name</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>price</name>
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
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
            </declaration>
        </table>
        ';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Sales'), 
            'sales_products', 
            1
        );
        
        // check if crm is installed first
        if (Setup_Controller::getInstance()->isInstalled('Crm')) {
        
            // add products from crm
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'metacrm_products');
            $stmt = $this->_db->query($select);
            $queryResult = $stmt->fetchAll();
    
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($queryResult, TRUE));
            
            // insert values into products table
            $productsBackend = new Tinebase_Backend_Sql('Sales_Model_Product', 'sales_products');
            foreach ($queryResult as $row) {
                $products = new Sales_Model_Product(array(
                    'id'    => $row['id'],
                    'name'  => $row['productsource'],
                    'price' => $row['price'],
                ));
                $productsBackend->create($products);
            }
        }
            
        $this->setApplicationVersion('Sales', '2.2');
    }
    
    /**
     * - use relations to save lead products
     * - remove old crm products tables
     * 
     * @return void
     */
    public function update_2()
    {
        if (Setup_Controller::getInstance()->isInstalled('Crm')) {
            // get linked products
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'metacrm_leads_products');
            $stmt = $this->_db->query($select);
            $queryResult = $stmt->fetchAll();
    
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($queryResult, TRUE));
            
            // insert values into relations table
            $relationsBackend = new Tinebase_Relation_Backend_Sql();
            foreach ($queryResult as $row) {
                $relation = new Tinebase_Model_Relation( array(
                    'own_model'              => 'Crm_Model_Lead',
                    'own_backend'            => 'Sql',
                    'own_id'                 => $row['lead_id'],
                    'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'type'                   => 'PRODUCT',
                    'related_model'          => 'Sales_Model_Product',
                    'related_backend'        => 'Sql',
                    'related_id'             => $row['product_id'],
                    'remark'                => Zend_Json::encode(array(
                        'description'   => $row['product_desc'],
                        'price'         => $row['product_price'],
                        'quantity'      => 1,
                    ))
                ));
                try {
                    $relationsBackend->addRelation($relation);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                        . ' Found duplicate, increasing quantity (' . $zdse->getMessage() . ')');
                    
                    // increase quantity
                    $updateRelation = $relationsBackend->search(new Tinebase_Model_RelationFilter(array(
                        array('field' => 'own_id',           'operator' => 'equals',       'value' => $relation->own_id),
                        array('field' => 'related_id',       'operator' => 'equals',       'value' => $relation->related_id),
                        array('field' => 'related_model',    'operator' => 'equals',       'value' => 'Sales_Model_Product'),
                    )))->getFirstRecord();
                    $remark = $updateRelation->remark;
                    $remark['quantity'] = $remark['quantity'] + 1;
                    $updateRelation->remark = $remark;
                    //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($updateRelation->toArray(), TRUE));
                    $relationsBackend->updateRelation($updateRelation);
                }
            }
            
            // drop table metacrm_leadsproducts and metacrm_products 
            $this->dropTable('metacrm_leads_products');
            $this->dropTable('metacrm_products');
        }
        
        $this->setApplicationVersion('Sales', '2.3');
    }
    
    /**
     * add manufacturer and category to products
     * 
     * @return void
     */
    public function update_3()
    {
        $newFields = array('manufacturer', 'category');
        
        foreach ($newFields as $fieldName) {
            $field = '<field>
                        <name>' . $fieldName . '</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
                    </field>';
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('sales_products', $declaration);
        }
        
        $this->setApplicationVersion('Sales', '2.4');
        $this->setTableVersion('sales_products', '2');
    }
    
    /**
     * erp was renamed to sales -> update models in relations
     * 
     * @return void
     */
    public function update_4()
    {
        $this->_db->query("update " . SQL_TABLE_PREFIX . "relations set own_model='Sales_Model_Contract' where own_model='Erp_Model_Contract'");
        $this->_db->query("update " . SQL_TABLE_PREFIX . "relations set related_model='Sales_Model_Contract' where related_model='Erp_Model_Contract'");
        
        $this->setApplicationVersion('Sales', '2.5');
    }
    
    /**
     * update to 3.0
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('Sales', '3.0');
    }
}
