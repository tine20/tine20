<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Tinebase_Setup_Update_Release3 extends Setup_Update_Abstract
{    
    /**
     * update to 3.1
     * - add value_search option field to customfield_config
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>value_search</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('customfield_config', $declaration);
        
        $this->setTableVersion('customfield_config', '4');
        $this->setApplicationVersion('Tinebase', '3.1');
    }    

    /**
     * update to 3.2
     * - add personal_only field to preference
     * - remove all admin/default prefs with this setting
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>personal_only</name>
                <type>boolean</type>
            </field>');
        try {
            $this->_backend->addCol('preferences', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // field already exists
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
        }
        
        $this->setTableVersion('preferences', '5');
        
        // remove all personal only prefs for anyone
        $this->_db->query("DELETE FROM " . SQL_TABLE_PREFIX . "preferences WHERE account_type LIKE 'anyone' AND name IN ('defaultCalendar', 'defaultAddressbook')");
        
        $this->setApplicationVersion('Tinebase', '3.2');
    }    
    
    /**
     * update to 3.3
     * - change key of import export definitions table
     */
    public function update_2()
    {
        // we need to drop the foreign key and the index first
        $this->_backend->dropForeignKey('importexport_definition', 'importexport_definitions::app_id--applications::id');
        $this->_backend->dropIndex('importexport_definition', 'application_id-name-type');
        
        // add index and foreign key again
        $this->_backend->addIndex('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>model-name-type</name>
                <unique>true</unique>
                <field>
                    <name>model</name>
                </field>
                <field>
                    <name>name</name>
                </field>
                <field>
                    <name>type</name>
                </field>
            </index>')
        ); 
        $this->_backend->addForeignKey('importexport_definition', new Setup_Backend_Schema_Index_Xml('<index>
                <name>importexport_definitions::app_id--applications::id</name>
                <field>
                    <name>application_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>applications</table>
                    <field>id</field>
                </reference>
            </index>')
        );
        
        // increase versions
        $this->setTableVersion('importexport_definition', '3');
        $this->setApplicationVersion('Tinebase', '3.3');
    }
    
    /**
     * update to 3.4
     * - add filename field to import/export definitions
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                    <name>filename</name>
                    <type>text</type>
                    <length>40</length>
                </field>');
        $this->_backend->addCol('importexport_definition', $declaration);
        
        $this->setTableVersion('importexport_definition', '4');
        $this->setApplicationVersion('Tinebase', '3.4');
    }    

    /**
     * update to 3.5
     * - set filename field in export definitions (name + .xml)
     */
    public function update_4()
    {
        $this->_db->query("UPDATE " . SQL_TABLE_PREFIX . "importexport_definition SET filename=CONCAT(name,'.xml') WHERE type = 'export'");
        $this->setApplicationVersion('Tinebase', '3.5');
    }
    
    /**
     * update to 3.6
     * - container_acl -> int to string
     */
    public function update_5()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
         <field>
            <name>account_grant</name>
            <type>text</type>
            <length>40</length>
            <notnull>true</notnull>
        </field>');
        
        $this->_backend->alterCol('container_acl', $declaration);
        
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='readGrant' WHERE `account_grant` = '1'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='addGrant' WHERE `account_grant` = '2'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='editGrant' WHERE `account_grant` = '4'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='deleteGrant' WHERE `account_grant` = '8'");
        $this->_db->query("UPDATE `" . SQL_TABLE_PREFIX . "container_acl` SET `account_grant`='adminGrant' WHERE `account_grant` = '16'");
        
        $this->setTableVersion('container_acl', '2');
        $this->setApplicationVersion('Tinebase', '3.6');
    }
}
