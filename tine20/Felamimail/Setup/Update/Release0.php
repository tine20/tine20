<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Felamimail updates for version 0.x
 *
 * @package     Felamimail
 * @subpackage  Setup
 */
class Felamimail_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add 'none' to smtp_auth for accounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
                    <name>smtp_auth</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>login</value>
                    <value>plain</value>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('felamimail_account', $declaration);
        
        $this->setApplicationVersion('Felamimail', '0.2');
        $this->setTableVersion('felamimail_account', '2');
    }

    /**
     * update function 2
     * - add namespaces for accounts
     *
     */    
    public function update_2()
    {
        $newFields = array('ns_personal', 'ns_other', 'ns_shared');
        
        foreach ($newFields as $field) {
            $field = '<field>
                <name>' . $field . '</name>
                <type>text</type>
                <length>256</length>
            </field>';
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->addCol('felamimail_account', $declaration);
        }
        
        $this->setApplicationVersion('Felamimail', '0.3');
        $this->setTableVersion('felamimail_account', '3');
    }
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('felamimail_account', $declaration, 'created_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('felamimail_account', $declaration, 'last_modified_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('felamimail_account', $declaration, 'deleted_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>user_id</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('felamimail_account', $declaration, 'user_id');
        
        $this->setTableVersion('felamimail_account', 4);
        $this->setTableVersion('felamimail_folder', 2);
        $this->setApplicationVersion('Felamimail', '0.4');
    }

    /**
     * update function 4
     * - add sort folders setting to accounts
     *
     */    
    public function update_4()
    {
        $field = '<field>
                    <name>sort_folders</name>
                    <type>boolean</type>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('felamimail_account', $declaration);
        
        $this->setApplicationVersion('Felamimail', '0.5');
        $this->setTableVersion('felamimail_account', 5);
    }

    /**
     * update function 5 -> 2.0
     * - add display format option
     *
     */    
    public function update_5()
    {
        $field = '<field>
                    <name>display_format</name>
                    <type>enum</type>
                    <default>html</default>
                    <value>html</value>
                    <value>plain</value>
                </field>';
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('felamimail_account', $declaration);
        
        $this->setApplicationVersion('Felamimail', '2.0');
        $this->setTableVersion('felamimail_account', 6);
    }
}
