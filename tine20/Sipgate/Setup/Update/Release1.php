<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

/**
 * Sipgate updates for version 1.1
 *
 * @package     Sipgate
 * @subpackage  Setup
 */
class Sipgate_Setup_Update_Release1 extends Setup_Update_Abstract
{

    /**
     * update 0.1 -> 1.0
     */
    public function update_0()
    {
        $this->setApplicationVersion('Sipgate', '1.0');
    }


    /**
     * update 1.0 -> 1.1
     */
    public function update_1()
    {
        $this->setApplicationVersion('Sipgate', '1.1');
    }

    public function update_2()
    {
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sipgate')->getId();

        $connectionStatusConfig = array(
            'name'    => Sipgate_Config::CONNECTION_STATUS,
            'records' => array(
                array('id' => 'accepted', 'value' => 'accepted', 'icon' => "../../images/../Sipgate/res/call_accepted.png", 'system' => true),  //_('accepted')
                array('id' => 'outgoing', 'value' => 'outgoing', 'icon' => "../../images/../Sipgate/res/call_outgoing.png", 'system' => true),  //_('outgoing')
                array('id' => 'missed',   'value' => 'missed',   'icon' => "../../images/../Sipgate/res/call_missed.png", 'system' => true),  //_('missed')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::CONNECTION_STATUS,
            'value'             => json_encode($connectionStatusConfig),
        )));

        // create tos config
        $connectionTos = array(
            'name'    => Sipgate_Config::CONNECTION_TOS,
            'records' => array(
                array('id' => 'voice', 'value' => 'Telephone Call',  'icon' => "../../images/oxygen/16x16/apps/kcall.png",   'system' => true),  //_('Telephone Call')
                array('id' => 'fax',   'value' => 'Facsimile',       'icon' => "../../images/../Sipgate/res/16x16/kfax.png", 'system' => true),  //_('Facsimile')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::CONNECTION_TOS,
            'value'             => json_encode($connectionTos),
        )));
        $this->setApplicationVersion('Sipgate', '1.2');
    }
    
    public function update_3()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sipgate')->getId();

        $c = array(
            'name'    => Sipgate_Config::ACCOUNT_ACCOUNT_TYPE,
            'records' => array(
                array('id' => 'plus', 'value' => 'basic/plus', 'icon' => "../../images/oxygen/16x16/places/user-identity.png", 'system' => true),  //_('basic/plus')
                array('id' => 'team', 'value' => 'team',       'icon' => "../../images/oxygen/16x16/apps/system-users.png",    'system' => true),  //_('team')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::ACCOUNT_ACCOUNT_TYPE,
            'value'             => json_encode($c),
        )));

        // create tos config
        $c = array(
            'name'    => Sipgate_Config::ACCOUNT_TYPE,
            'records' => array(
                array('id' => 'private', 'value' => 'private', 'icon' => "../../images/oxygen/16x16/places/user-identity.png", 'system' => true),  //_('private')
                array('id' => 'shared',     'value' => 'shared',   'icon' => "../../images/oxygen/16x16/apps/system-users.png",    'system' => true),  //_('shared')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sipgate_Config::ACCOUNT_TYPE,
            'value'             => json_encode($c),
        )));

        $this->setApplicationVersion('Sipgate', '1.4');
    }
    /**
     * creates the new tables
     */
    public function update_4() {
        $tableDefinition = '<table>
            <name>sipgate_account</name>
            <version>0</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>accounttype</name>
                    <type>text</type>
                    <length>10</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>type</name>
                    <type>text</type>
                    <length>10</length>
                    <notnull>true</notnull>
                    <default>shared</default>
                </field>
                <field>
                    <name>credential_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>description</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>username</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>password</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>is_valid</name>
                    <type>boolean</type>
                    <default>false</default>
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
                <field>
                    <name>mobile_number</name>
                    <type>text</type>
                    <length>64</length>
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
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        
        $tableDefinition = '<table>
            <name>sipgate_line</name>
            <version>0</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field> 
                <field>
                    <name>user_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
                <field>
                    <name>uri_alias</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>sip_uri</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>e164_out</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>e164_in</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>tos</name>
                    <type>text</type>
                    <length>10</length>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>last_sync</name>
                    <type>datetime</type>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>sipgate_line::account_id--sipgate_account::id</name>
                    <field>
                        <name>account_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>sipgate_account</table>
                        <field>id</field>
                    </reference>
                </index>
                <index>
                    <name>sipgate_line::user_id--sipgate_user::id</name>
                    <field>
                        <name>user_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>accounts</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        
        $tableDefinition = '<table>
            <name>sipgate_connection</name>
            <version>0</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>entry_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>tos</name>
                    <type>text</type>
                    <length>10</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>local_uri</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>remote_uri</name>
                    <type>text</type>
                    <length>256</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>local_number</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>remote_number</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>status</name>
                    <type>text</type>
                    <length>8</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>line_id</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>timestamp</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>creation_time</name>
                    <type>datetime</type>
                </field> 
                <field>
                    <name>tarif</name>
                    <type>text</type>
                    <length>256</length>
                </field>
                <field>
                    <name>duration</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>units_charged</name>
                    <type>integer</type>
                </field>
                <field>
                    <name>price_unit</name>
                    <type>float</type>
                </field>
                <field>
                    <name>price_total</name>
                    <type>float</type>
                </field>
                <field>
                    <name>ticks_a</name>
                    <type>integer</type>
                    <length>3</length>
                </field>
                <field>
                    <name>ticks_b</name>
                    <type>integer</type>
                    <length>3</length>
                </field>
                <field>
                    <name>contact_id</name>
                    <type>text</type>
                    <length>40</length>
                </field>
                <field>
                    <name>contact_name</name>
                    <type>text</type>
                    <length>128</length>
                    <notnull>false</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>entry_id</name>
                    <field>
                        <name>entry_id</name>
                    </field>
                </index>
                <index>
                    <name>creation_time</name>
                    <field>
                        <name>creation_time</name>
                    </field>
                </index>
                <index>
                    <name>timestamp</name>
                    <field>
                        <name>timestamp</name>
                    </field>
                </index>                
                <index>
                    <name>connection::contact_id--contact::id</name>
                    <field>
                        <name>contact_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>addressbook</table>
                        <field>id</field>
                    </reference>
                </index>
                <index>
                    <name>connection::line_id--line::id</name>
                    <field>
                        <name>line_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>sipgate_line</table>
                        <field>id</field>
                    </reference>
                </index>
            </declaration>
        </table>';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table);
        
        $this->setApplicationVersion('Sipgate', '1.5');
    }
    /**
     * creates an account if previous config was found
     */
    public function update_5()
    {
        
        $this->setApplicationVersion('Sipgate', '2.0');
    }
}
