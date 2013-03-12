<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * updates for major release 6
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * update to 6.1
     * - fix cascade statements
     * - extend primary key to counter
     * - add new column pendingdata
     * 
     * @return void
     */
    public function update_0()
    {
        $this->validateTableVersion('acsync_device', 3);

        // remove all policy_id's and remove foreign key
        $this->_backend->dropForeignKey('acsync_device', 'acsync_device::policy_id--acsync_policy::id');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>policy_id</name>
                <type>text</type>
                <length>40</length>
            </field>
        ');
        $this->_backend->alterCol('acsync_device', $declaration);
        
        $this->_db->update(SQL_TABLE_PREFIX . 'acsync_device', array(
            'policy_id' => null,
        ));
        
        $activeSyncAppId = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync')->getId();
        $this->_backend->dropTable('acsync_policy', $activeSyncAppId);
        
        $declaration = new Setup_Backend_Schema_Table_Xml('
            <table>
                <name>acsync_policy</name>
                <version>2</version>
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
                        <length>64</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>description</name>
                        <type>text</type>
                        <length>255</length>
                    </field>
                    <field>
                        <name>policy_key</name>
                        <type>text</type>
                        <length>64</length>
                        <notnull>true</notnull>
                    </field>
                    <field>
                        <name>allow_bluetooth</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_browser</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_camera</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_consumer_email</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_desktop_sync</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_h_t_m_l_email</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_internet_sharing</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_ir_d_a</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_p_o_p_i_m_a_p_email</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_remote_desktop</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_simple_device_password</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_s_m_i_m_e_encryption_algorithm_negotiation</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_s_m_i_m_e_soft_certs</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_storage_card</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_text_messaging</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_unsigned_applications</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_unsigned_installation_packages</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>allow_wifi</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>alphanumeric_device_password_required</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>approved_application_list</name>
                        <type>blob</type>
                    </field>
                    <field>
                        <name>attachments_enabled</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>device_password_enabled</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>device_password_expiration</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>device_password_history</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_attachment_size</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_calendar_age_filter</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_device_password_failed_attempts</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_email_age_filter</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_email_body_truncation_size</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_email_h_t_m_l_body_truncation_size</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>max_inactivity_time_device_lock</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>min_device_password_complex_characters</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>min_device_password_length</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>password_recovery_enabled</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_device_encryption</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_encrypted_s_m_i_m_e_messages</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_encryption_s_m_i_m_e_algorithm</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_manual_sync_when_roaming</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_signed_s_m_i_m_e_algorithm</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_signed_s_m_i_m_e_messages</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>require_storage_card_encryption</name>
                        <type>boolean</type>
                    </field>
                    <field>
                        <name>unapproved_in_r_o_m_application_list</name>
                        <type>blob</type>
                    </field>
                    <index>
                        <name>id</name>
                        <primary>true</primary>
                        <unique>true</unique>
                        <field>
                            <name>id</name>
                        </field>
                    </index>
                </declaration>
            </table>
        ');
        $this->_backend->createTable($declaration, 'ActiveSync', 'acsync_policy');
        $this->setTableVersion('acsync_policy', 2);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>acsync_device::policy_id--acsync_policy::id</name>
                <field>
                    <name>policy_id</name>
                </field>
                <foreign>true</foreign>
                <reference>
                    <table>acsync_policy</table>
                    <field>id</field>
                    <onupdate>cascade</onupdate>
                </reference>
            </index>
        ');
        $this->_backend->addForeignKey('acsync_device', $declaration);
        $this->setTableVersion('acsync_device', 4);
        
        $declaration = new SimpleXMLElement("
            <record>
                <table>
                    <name>acsync_policy</name>
                </table>
                <field>
                    <name>id</name>
                    <value special='uid'/>
                </field>
                <field>
                    <name>name</name>
                    <value>Default Policy</value>
                    <!-- gettext('Default Policy') -->
                </field>
                <field>
                    <name>description</name>
                    <value>Default Policy installed during setup</value>
                    <!-- gettext('Default Policy installed during setup') -->
                </field>
                <field>
                    <name>policy_key</name>
                    <value special='uid'/>
                </field>
                <field>
                    <name>device_password_enabled</name>
                    <value>1</value>
                </field>
            </record>
        ");
        $this->_backend->execInsertStatement($declaration);
        
        $this->setApplicationVersion('ActiveSync', '6.1');
    }
    
    /**
     * update to 6.2
     * - add new column lastsynccollection
     * - add new column supportedfields
     * 
     * @return void
     */
    public function update_1()
    {
        $this->validateTableVersion('acsync_device', 4);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastsynccollection</name>
                <type>blob</type>
                <default>null</default>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $this->setTableVersion('acsync_device', 5);
        
        
        $this->validateTableVersion('acsync_folder', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>supportedfields</name>
                <type>blob</type>
                <default>null</default>
            </field>
        ');
        $this->_backend->addCol('acsync_folder', $declaration);
        
        $this->setTableVersion('acsync_folder', 3);
        
        $this->setApplicationVersion('ActiveSync', '6.2');
    }
    
    /**
     * update to 6.3
     * 
     * @see 0007394: Index missing for id column in acsync_synckey
     */
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>id</name>
                <unique>true</unique>
                <field>
                    <name>id</name>
                </field>
            </index>
        ');
        try {
            $this->_backend->addIndex('acsync_synckey', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // no index could be added
        }
        $this->setTableVersion('acsync_synckey', 4);
        $this->setApplicationVersion('ActiveSync', '6.3');
    }
    
    /**
     * update to 6.4
     * 
     * @see 0007452: use json encoded array for saving of policy settings
     */
    public function update_3()
    {
        $fieldsToRemove = array(
            'allow_bluetooth',
            'allow_browser',
            'allow_camera',
            'allow_consumer_email',
            'allow_desktop_sync',
            'allow_h_t_m_l_email',
            'allow_internet_sharing',
            'allow_ir_d_a',
            'allow_p_o_p_i_m_a_p_email',
            'allow_remote_desktop',
            'allow_simple_device_password',
            'allow_s_m_i_m_e_encryption_algorithm_negotiation',
            'allow_s_m_i_m_e_soft_certs',
            'allow_storage_card',
            'allow_text_messaging',
            'allow_unsigned_applications',
            'allow_unsigned_installation_packages',
            'allow_wifi',
            'alphanumeric_device_password_required',
            'approved_application_list',
            'attachments_enabled',
            'device_password_enabled',
            'device_password_expiration',
            'device_password_history',
            'max_attachment_size',
            'max_calendar_age_filter',
            'max_device_password_failed_attempts',
            'max_email_age_filter',
            'max_email_body_truncation_size',
            'max_email_h_t_m_l_body_truncation_size',
            'max_inactivity_time_device_lock',
            'min_device_password_complex_characters',
            'min_device_password_length',
            'password_recovery_enabled',
            'require_device_encryption',
            'require_encrypted_s_m_i_m_e_messages',
            'require_encryption_s_m_i_m_e_algorithm',
            'require_manual_sync_when_roaming',
            'require_signed_s_m_i_m_e_algorithm',
            'require_signed_s_m_i_m_e_messages',
            'require_storage_card_encryption',
            'unapproved_in_r_o_m_application_list'
        );
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>json_policy</name>
                <type>blob</type>
            </field>
        ');
        $this->_backend->addCol('acsync_policy', $declaration);
        
        // read values from old columns and convert to JSON encoded array
        $select = $this->_db->select()
            ->from(
                array('acsync_policy' => SQL_TABLE_PREFIX . 'acsync_policy')
            );
        $result = $this->_db->fetchAll($select);
        
        foreach ($result as $row) {
            $policy = array();
            
            foreach ($row as $key => $value) {
                if (! in_array($key, $fieldsToRemove)) {
                    continue;
                }
                
                if ($value !== null) {
                    $policy[$this->_toCamelCase($key)] = $value;
                }
            }
            
            // update json_policy
            $this->_db->update(
                SQL_TABLE_PREFIX . 'acsync_policy', 
                array(
                    'json_policy' => Zend_Json::encode($policy)
                ), 
                $this->_db->quoteInto("id = ?", $row['id']));
        }
        
        // drop old columns
        foreach ($fieldsToRemove as $fieldName) {
            $this->_backend->dropCol('acsync_policy', $fieldName);
        }
        
        $this->setTableVersion('acsync_policy', 3);
        
        $this->setApplicationVersion('ActiveSync', '6.4');
    }
    
    /**
     * convert from camel_case to camelCase
     * 
     * @param  string $string
     * @param  bool   $ucFirst
     * @return string
     */
    protected function _toCamelCase($string, $ucFirst = true)
    {
        if ($ucFirst === true) {
            $string = ucfirst($string);
        }
        
        return preg_replace_callback('/_([a-z])/', function ($string) {return strtoupper($string[1]);}, $string);
    }
    
   /**
    * update to 6.5
    * 
    * @see 0007942: reset device pingfolder in update script
    * 
    */
    public function update_4()
    {
        $sql = "update " . SQL_TABLE_PREFIX . "acsync_device set pingfolder = null where pingfolder like '%Syncope%'";
        $this->_db->query($sql);
        $this->setApplicationVersion('ActiveSync', '6.5');
    }
    
    /**
     * update to 7.0
     *
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('ActiveSync', '7.0');
    }
}
