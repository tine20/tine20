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

class Tinebase_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 1.1
     * - add default app
     *
     * @deprecated we now have default prefs
     */    
    public function update_0()
    {
        // add default app preference
        $defaultAppPref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Preference::DEFAULT_APP,
            'value'             => 'Addressbook',
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <special>' . Tinebase_Preference::DEFAULT_APP . '</special>
                </options>'
        ));
        Tinebase_Core::getPreference()->create($defaultAppPref);
        
        $this->setApplicationVersion('Tinebase', '1.1');
    }

    /**
     * update to 1.2
     * - add window style
     */
    public function update_1()
    {
        // add window type preference
        $windowStylePref = new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => Tinebase_Preference::WINDOW_TYPE,
            'value'             => 'Browser',
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <option>
                        <label>ExtJs style</label>
                        <value>Ext</value>
                    </option>
                    <option>
                        <label>Browser style</label>
                        <value>Browser</value>
                    </option>
                </options>'
        ));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($windowStylePref->toArray(), TRUE));
        
        Tinebase_Core::getPreference()->create($windowStylePref);
        
        $this->setApplicationVersion('Tinebase', '1.2');
    }

    /**
     * update to 1.3
     * - add alarm table
     */
    public function update_2()
    {
        $tableDefinition = '
        <table>
            <name>alarm</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>model</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>alarm_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>sent_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>sent_status</name>
                    <type>enum</type>
                    <value>pending</value>
                    <value>failure</value>
                    <value>success</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>sent_message</name>
                    <type>text</type>
                </field>
                <field>
                    <name>options</name>
                    <type>text</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>record_id-model</name>
                    <unique>true</unique>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>model</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'alarm', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '1.3');
    }

    /**
     * update to 1.4
     * - add async events table
     */
    public function update_3()
    {
        $tableDefinition = '
        <table>
            <name>async_job</name>
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
                    <length>256</length>
                </field>
                <field>
                    <name>start_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>end_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>status</name>
                    <type>enum</type>
                    <value>running</value>
                    <value>failure</value>
                    <value>success</value>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>message</name>
                    <type>text</type>
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
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'async_job', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '1.4');
    }
    
    /**
     * update to 1.4
     * - add async events table
     */
    public function update_4()
    {
        /*
         * drop old indexes
         */
        $this->_backend->dropForeignKey('accounts',      'accounts::primary_group_id--groups::id');
        $this->_backend->dropForeignKey('group_members', 'group_members::account_id--accounts::id');
        $this->_backend->dropForeignKey('group_members', 'group_members::group_id--groups::id');        
        /*
         * update column definition
         */
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'id');
        $this->_backend->alterCol('groups', $declaration, 'id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>primary_group_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('accounts', $declaration, 'primary_group_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>group_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('group_members', $declaration, 'group_id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('group_members', $declaration, 'account_id');
        
        /*
         * readd foreign keys
         */        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>accounts::primary_group_id--groups::id</name>
                <field>
                    <name>primary_group_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>groups</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>group_members::group_id--groups::id</name>
                <field>
                    <name>group_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>groups</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('group_members', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>group_members::account_id--accounts::id</name>
                <field>
                    <name>account_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>accounts</table>
                    <field>id</field>
                </reference>
            </index>   
        ');
        $this->_backend->addForeignKey('group_members', $declaration);
        
        $this->setApplicationVersion('Tinebase', '1.5');
    }
}
