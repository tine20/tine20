<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Sales_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * - 0012358: purchase invoice description column too short
     */
    public function update_0()
    {
        if ($this->getTableVersion('sales_purchase_invoices') < 4) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>description</name>
                    <type>text</type>
                </field>
        ');
            $this->_backend->alterCol('sales_purchase_invoices', $declaration);
            $this->setTableVersion('sales_purchase_invoices', 4);
        }
        $this->setApplicationVersion('Sales', '10.1');
    }

    /**
     * update to 10.2
     *
     * Add fulltext index for description field of sales_contracts
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_contracts', $declaration);
        }  catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_contracts', 9);
        $this->setApplicationVersion('Sales', '10.2');
    }

    /**
     * update to 10.3
     *
     * Add fulltext index for description field of sales_products
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_products', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_products', 7);
        $this->setApplicationVersion('Sales', '10.3');
    }

    /**
     * update to 10.4
     *
     * Add fulltext index for description field of sales_customers
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_customers', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_customers', 2);
        $this->setApplicationVersion('Sales', '10.4');
    }

    /**
     * update to 10.5
     *
     * Add fulltext index for description field of sales_suppliers
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_suppliers', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_suppliers', 2);
        $this->setApplicationVersion('Sales', '10.5');
    }

    /**
     * update to 10.6
     *
     * Add fulltext index for description field of sales_purchase_invoices
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_purchase_invoices', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_purchase_invoices', 5);
        $this->setApplicationVersion('Sales', '10.6');
    }

    /**
     * update to 10.7
     *
     * Add fulltext index for description field of sales_sales_invoices
     */
    public function update_6()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_sales_invoices', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_sales_invoices', 7);
        $this->setApplicationVersion('Sales', '10.7');
    }
    
    /**
     * update to 10.8
     *
     * Add fulltext index for description field of sales_offers
     */
    public function update_7()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                    <name>description</name>
                </field>
            </index>
        ');

        try {
            $this->_backend->addIndex('sales_offers', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_offers', 2);
        $this->setApplicationVersion('Sales', '10.8');
    }

    /**
     * update to 10.9
     *
     * Add fulltext index for description field of sales_offers
     */
    public function update_8()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('<index>
                <name>description</name>
                <fulltext>true</fulltext>
                <field>
                <name>description</name>
                </field>
            </index>');

        try {
            $this->_backend->addIndex('sales_orderconf', $declaration);
        } catch (Exception $e) {
            // might have already been added by \Setup_Controller::upgradeMysql564
            Tinebase_Exception::log($e);
        }

        $this->setTableVersion('sales_orderconf', 2);
        $this->setApplicationVersion('Sales', '10.9');
    }

    /**
     * update to 10.10
     */
    public function update_9()
    {
        if ($this->getTableVersion('sales_contracts') < 10) {
            $this->setTableVersion('sales_contracts', 10);
        }
        $this->setApplicationVersion('Sales', '10.10');
    }

    /**
     * update to 11.0
     */
    public function update_10()
    {
        $this->setApplicationVersion('Sales', '11.0');
    }
}
