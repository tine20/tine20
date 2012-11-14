<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Crm_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - drop obsolete tables metacrm_leads_products + metacrm_products
     *   (data has already been converted in Sales_Setup_Update_Release2::update_2)
     * - change id column of lead table to varchar(40)
     * 
     * @return void
     */
    public function update_0()
    {
        // drop table metacrm_leadsproducts and metacrm_products 
        try {
            $this->dropTable('metacrm_leads_products');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already dropped
        }
        try {
            $this->dropTable('metacrm_products');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // already dropped
        }
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration);
        $this->setTableVersion('metacrm_lead', '6');
        
        $this->setApplicationVersion('Crm', '6.1');
    }
    
    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Crm', '7.0');
    }
}
