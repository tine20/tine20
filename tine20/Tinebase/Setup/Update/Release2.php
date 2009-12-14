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

class Tinebase_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update to 2.1
     * - rename config_customfields to customfield_config and add customfield table
     */    
    public function update_0()
    {
        // $this->validateTableVersion('config_customfields', '2');
        $this->renameTable('config_customfields', 'customfield_config');
        $this->setTableVersion('customfield_config', '3');
        
        $tableDefinition = '
        <table>
            <name>customfield</name>
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
                    <name>customfield_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>value</name>
                    <type>text</type>
                </field>
                <index>
                    <name>record_id_customfield_id</name>
                    <unique>true</unique>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>customfield_id</name>
                    </field>
                </index>
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition); 
        $this->_backend->createTable($table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'customfield', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '2.1');
    }
    
    /**
     * update to 2.2
     * - rename tables and fields to be at most 30 characters long (needed for Oracle support)
     *  
     */    
    public function update_1()
    {
        $this->renameTable('registration_invitations', 'registration_invitation');
        $this->setTableVersion('registration_invitation', '2');
        
        $this->renameTable('record_persistentobserver', 'record_observer');
        $this->setTableVersion('record_observer', '2');
        
        $this->renameTable('importexport_definitions', 'importexport_definition');
        $this->setTableVersion('importexport_definition', '2');
        
        $this->setApplicationVersion('Tinebase', '2.2');
    }
    
    /**
     * update to 2.3
     * - move accounts storage configuration from config.inc.php to config db table
     * - changed the way the configuration for default user group name and default admin group name is stored
     *  
     */    
    public function update_2()
    {
        $config = Setup_Controller::getInstance()->getConfigData();
        if (!empty($config['accounts'])) {
			if (empty($config['accounts']['backend'])) {
				$config['accounts']['backend'] = 'Sql';
			}
            $backendType = ucfirst($config['accounts']['backend']);
            Tinebase_User::setBackendType($backendType);
            
            //add default settings
            $defaultConfig = Tinebase_User::getBackendConfigurationDefaults($backendType);
            Tinebase_User::setBackendConfiguration($defaultConfig);
            
            //override default settings with config.inc.php settings
            if (!empty($config['accounts'][$config['accounts']['backend']])) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting config: ' . print_r($config['accounts'][$config['accounts']['backend']], TRUE));
                Tinebase_User::setBackendConfiguration($config['accounts'][$config['accounts']['backend']]);
            } 
            
            //delete old config settings from config.inc.php
            unset($config['accounts']);
        }
        
        $defaultUserGroupName = Tinebase_Config::getInstance()->getConfig('Default User Group', null, 'Users');
        $defaultAdminGroupName = Tinebase_Config::getInstance()->getConfig('Default Admin Group', null, 'Administrators');
        Tinebase_User::setBackendConfiguration($defaultUserGroupName->value, Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
        Tinebase_User::setBackendConfiguration($defaultAdminGroupName->value, Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY);
        
        //write changes to config table
        Tinebase_User::saveBackendConfiguration();
        
        Tinebase_Config::getInstance()->deleteConfig($defaultUserGroupName);
        Tinebase_Config::getInstance()->deleteConfig($defaultAdminGroupName);
        
        $this->setApplicationVersion('Tinebase', '2.3');
    }
    
    /**
     * update to 2.4
     * - move email configuration from config.inc.php to config db table
     */    
    public function update_3()
    {
        $config = Setup_Controller::getInstance()->getConfigData();
        
        // get imap settings -> felamimail config
        if (isset($config['imap'])) {
            $config['imap']['active'] = 1;
            if (isset($config['imap']['secure_connection'])) {
                $config['imap']['ssl'] = $config['imap']['secure_connection'];
                unset($config['imap']['secure_connection']);
            }
            Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::IMAP, Zend_Json::encode($config['imap']));
            unset($config['imap']);
        }
        
        // get smtp settings -> tinebase config
        if (isset($config['smtp'])) {
            $config['smtp']['active'] = 1;
            Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::SMTP, Zend_Json::encode($config['smtp']));
            unset($config['smtp']);
        }
        
        // delete old config settings from config.inc.php
        // do we want that?
        //Setup_Controller::getInstance()->saveConfigData($config, FALSE);
        
        $this->setApplicationVersion('Tinebase', '2.4');
    }

    /**
     * update to 2.5
     * - replace serialized data in config with json encoded data
     */    
    public function update_4()
    {
        $rawBackendConfiguration = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::USERBACKEND, null, array())->value;
        if (substr($rawBackendConfiguration,0, 1) != '{') {
            $decodedConfig = unserialize($rawBackendConfiguration);
            Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::USERBACKEND, Zend_Json::encode($decodedConfig));
        }
        
        $this->setApplicationVersion('Tinebase', '2.5');
    }
    
    /**
     * update to 2.6
     * - move authentication storage configuration from config.inc.php to config db table
     *  
     */    
    public function update_5()
    {
        $config = Setup_Controller::getInstance()->getConfigData();
        if (!empty($config['authentication'])) {
            if (empty($config['authentication']['backend'])) {
                $config['authentication']['backend'] = 'Sql';
            }
            $backendType = ucfirst($config['authentication']['backend']);
            Tinebase_Auth::setBackendType($backendType);
            
            //add default config settings
            $defaultConfig = Tinebase_Auth::getBackendConfigurationDefaults($backendType);
            Tinebase_Auth::setBackendConfiguration($defaultConfig);
            
            //override default settings with config.inc.php settings
            if (!empty($config['authentication'][$config['authentication']['backend']])) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting config: ' . print_r($config['authentication'][$config['authentication']['backend']], TRUE));
                Tinebase_Auth::setBackendConfiguration($config['authentication'][$config['authentication']['backend']]);
            }

            Tinebase_Auth::saveBackendConfiguration();
        }
        
        $this->setApplicationVersion('Tinebase', '2.6');
    }
    
    /**
     * update to 2.7
     * - rename config.inc.php parameter session.save_path to sessiondir
     */    
    public function update_6()
    {
        if (Setup_Core::configFileWritable()) {
            $config = Setup_Controller::getInstance()->getConfigData();
            
            if (empty($config['sessiondir']) && !empty($config['session.save_path'])) {
                $config['sessiondir'] = $config['session.save_path'];
            }
            Setup_Controller::getInstance()->saveConfigData($config, FALSE);
        }
        
        $this->setApplicationVersion('Tinebase', '2.7');
    }
    
    /**
     * update to 2.8
     * - add OpenId tables
     */    
    public function update_7()
    {
        $tableDefinition = '
            <table>
                <name>openid_assoc</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>macfunc</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>secret</name>
                        <type>text</type>
                        <length>254</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>expires</name>
                        <type>integer</type>
                        <notnull>true</notnull>
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
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'openid_assoc', 
            1
        );

        $tableDefinition = '
            <table>
                <name>openid_sites</name>
                <version>1</version>
                <declaration>
                    <field>
                        <name>id</name>
                        <type>text</type>
                        <length>40</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>user_identity</name>
                        <type>text</type>
                        <length>254</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>site</name>
                        <type>text</type>
                        <length>254</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>trusted</name>
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
                        <name>user_identity-site</name>
                        <unique>true</unique>
                        <length>40</length>
                        <field>
                            <name>user_identity</name>
                        </field>
                        <field>
                            <name>site</name>
                        </field>
                    </index>
                </declaration>
            </table>
        ';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
         
        $this->_backend->createTable($table);
        
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'), 
            'openid_sites', 
            1
        );
        
        $this->setApplicationVersion('Tinebase', '2.8');
    }

    /**
     * update to 2.9
     * - renamed erp to sales
     */    
    public function update_8()
    {
        try {
            $sales = Tinebase_Application::getInstance()->getApplicationByName('Erp');
            $sales->name = 'Sales';
            Tinebase_Application::getInstance()->updateApplication($sales);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Erp application is not installed.');
        }
        
        $this->setApplicationVersion('Tinebase', '2.9');
    }
    
    /**
     * update to 2.10
     * - increase relation remark field size
     */    
    public function update_9()
    {
        //update column definition
        $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>remark</name>
                    <type>text</type>
                </field>');
        $this->_backend->alterCol('relations', $declaration);
        
        $this->setTableVersion('relations', '4');
        $this->setApplicationVersion('Tinebase', '2.10');
    }
    
    /**
     * update to 2.11
     * - add openid column
     */    
    public function update_10()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>openid</name>
                <type>text</type>
                <length>254</length>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>openid</name>
                <unique>true</unique>
                <field>
                    <name>openid</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('accounts', $declaration);
        
        $this->setApplicationVersion('Tinebase', '2.11');
    }
    
    /**
     * update to 2.12
     * - modify openid_sites
     */    
    public function update_11()
    {
        $this->_backend->dropTable('openid_sites');
        
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>openid_sites</name>
                <version>1</version>
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
                        <name>site</name>
                        <type>text</type>
                        <length>254</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>trusted</name>
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
                        <name>account_id-site</name>
                        <unique>true</unique>
                        <field>
                            <name>account_id</name>
                        </field>
                        <field>
                            <name>site</name>
                        </field>
                    </index>
                    <index>
                        <name>openid_sites::account_id--accounts::id</name>
                        <field>
                            <name>account_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>accounts</table>
                            <field>id</field>
                        </reference>
                    </index>
                </declaration>
            </table>        
        ');
        $this->_backend->createTable($declaration);
                
        $this->setApplicationVersion('Tinebase', '2.12');
    }
    
    /**
     * update to 2.13
     * - add visibility column
     */    
    public function update_12()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>visibility</name>
                <type>enum</type>
                <value>hidden</value>
                <value>displayed</value>
                <default>displayed</default>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('accounts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>visibility</name>
                <field>
                    <name>visibility</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('accounts', $declaration);
        
        $this->setApplicationVersion('Tinebase', '2.13');
    }
    
    /**
     * update to 2.14
     * - accept terms and conditions
     */    
    public function update_13()
    {
        // NOTE: this update can only be applied if user accepted terms
        Setup_Controller::getInstance()->saveAcceptedTerms(1);
        
        $this->setApplicationVersion('Tinebase', '2.14');
    }
}
