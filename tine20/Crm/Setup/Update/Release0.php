<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Crm_Setup_Update_Release0 extends Setup_Update_Abstract
{
    public function update_0()
    {
        
    }
    
    /**
     * update function 1
     * renames metacrm_products to metacrm_leads_products
     * renames metacrm_productsource to metacrm_products
     * adds MANAGE_LEADS right to user role
     */    
    public function update_1()
    {
        $this->renameTable('metacrm_product', 'metacrm_leads_products');
        $this->renameTable('metacrm_productsource', 'metacrm_products');
        
        $this->setTableVersion('metacrm_leads_products', '2');
        $this->setTableVersion('metacrm_products', '2');
        
        // add MANAGE_LEADS right to user role
        $userRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
        if ($userRole) {
            $application = Tinebase_Application::getInstance()->getApplicationByName('Crm');
            Tinebase_Acl_Roles::getInstance()->addSingleRight(
                $userRole->getId(), 
                $application->getId(), 
                Crm_Acl_Rights::MANAGE_LEADS
            );
        }        

        $this->setApplicationVersion('Crm', '0.2');
    }
    
    /**
     * update function 2
     * adds created_by, creation_time, etc meta fields to leads table
     */    
    public function update_2()
    {
        $this->validateTableVersion('metacrm_lead', '1');
        
        $alterFields = array(
            'created' =>
                '<field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>', 
            'modifier' => 
                '<field>
                    <name>last_modified_by</name>
                    <type>integer</type>
                </field>',
            'modified' => 
                '<field>
                    <name>last_modified_time</name>
                    <type>datetime</type>
                </field>',
        );
        
        $newFields = array(
            '<field>
                <name>created_by</name>
                <type>integer</type>
            </field>',
            '<field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>',
            '<field>
                <name>deleted_by</name>
                <type>integer</type>
            </field>',            
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>'
        );

        foreach ($alterFields as $old => $field) {
            try {
                $declaration = new Setup_Backend_Schema_Field_Xml($field);
                $this->_backend->alterCol('metacrm_lead', $declaration, $old);
            } catch (Zend_Db_Statement_Exception $e) {
                echo $e->getMessage() . '<br/>';
            }
        }
        
        foreach ($newFields as $field) {
            try {
                $declaration = new Setup_Backend_Schema_Field_Xml($field);
                $this->_backend->addCol('metacrm_lead', $declaration);
            } catch (Zend_Db_Statement_Exception $e) {
                echo $e->getMessage() . '<br/>';
            }
        }

        $this->setTableVersion('metacrm_lead', '2');
        $this->setApplicationVersion('Crm', '0.3');
    }
    
    /**
     * update function 3
     * rename column container to container_id in leads table
     */    
    public function update_3()
    {
        $this->validateTableVersion('metacrm_lead', '2');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>container_id</name>
                <type>integer</type>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration, 'container');
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>container_id</name>
                <field>
                    <name>container_id</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('metacrm_lead', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>metacrm_lead::container_id--container::id</name>
                <field>
                    <name>container_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>container</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('metacrm_lead', $declaration);
        
        $this->setTableVersion('metacrm_lead', '3');
        $this->setApplicationVersion('Crm', '0.4');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration, 'created_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration, 'last_modified_by');

        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration, 'deleted_by');

        
        $this->setApplicationVersion('Crm', '0.5');
    }
    
    public function update_5()
    {
     
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>turnover</name>
                <type>float</type>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration, 'turnover');

        $this->setTableVersion('metacrm_lead', '4');

        $this->setApplicationVersion('Crm', '2.0');
    }
}
