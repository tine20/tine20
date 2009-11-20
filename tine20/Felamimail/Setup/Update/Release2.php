<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Release0.php 10122 2009-08-21 10:23:50Z p.schuele@metaways.de $
 */

class Felamimail_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update function (2.0 -> 2.1)
     * - rename (stmp_)secure_connection to ssl
     */    
    public function update_0()
    {
        $fields = array(
                'secure_connection' => '<field>
                    <name>ssl</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>TLS</value>
                    <value>SSL</value>
                </field>',
                'smtp_secure_connection' => '<field>
                    <name>smtp_ssl</name>
                    <type>enum</type>
                    <value>none</value>
                    <value>TLS</value>
                    <value>SSL</value>
                </field>');
        
        foreach ($fields as $oldname => $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->alterCol('felamimail_account', $declaration, $oldname);
        }
        
        $this->setApplicationVersion('Felamimail', '2.1');
        $this->setTableVersion('felamimail_account', '7');
    }

    /**
     * update function (2.1 -> 2.2)
     * - rename show_intelligent_folders to intelligent_folders (string has been too long)
     */    
    public function update_1()
    {
        $field = '<field>
            <name>intelligent_folders</name>
            <type>boolean</type>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('felamimail_account', $declaration, 'show_intelligent_folders');
        
        $this->setApplicationVersion('Felamimail', '2.2');
        $this->setTableVersion('felamimail_account', '8');
    }
    
    /**
     * 
     */    
    public function update_2()
    {
        // does nothing
    }
    
    /**
     * - added cache status 'deleting'
     */    
    public function update_3()
    {
        $field = '<field>
                    <name>cache_status</name>
                    <type>enum</type>
                    <value>pending</value>
                    <value>empty</value>
                    <value>complete</value>
                    <value>incomplete</value>
                    <value>updating</value>
                    <value>deleting</value>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('felamimail_folder', $declaration, 'cache_status');
        
        $this->setApplicationVersion('Felamimail', '2.4');
        $this->setTableVersion('felamimail_account', '4');        
    }
    
    /**
     * - add recent field to folders?
     * - add options field to accounts?
     */    
    /*
    public function update_2()
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
        
        $this->setApplicationVersion('Felamimail', '2.3');
        $this->setTableVersion('felamimail_account', 6);
    }
    */
}
