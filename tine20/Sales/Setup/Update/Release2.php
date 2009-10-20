<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        // add products from crm
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'metacrm_products');
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($queryResult, TRUE));
        
        // insert values into customfield table
        $productsController = Sales_Controller_Product::getInstance();
        foreach ($queryResult as $row) {
            $products = new Sales_Model_Product(array(
                'id'    => $row['id'],
                'name'  => $row['productsource'],
                'price' => $row['price'],
            ));
            $productsController->create($products);
        }
            
        $this->setApplicationVersion('Sales', '2.2');
    }
    
    /**
     * - use relations to save lead products
     * - remove old crm products tables
     * 
     * @return void
     * 
     * @todo implement
     */
    public function update_3()
    {
        //-- drop table metacrm_leadsproducts
        //-- drop table metacrm_products
        
        //$this->setApplicationVersion('Sales', '2.3');
    }
    
}
