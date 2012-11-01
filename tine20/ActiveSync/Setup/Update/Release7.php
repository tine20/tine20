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
 * updates for major release 7
 *
 * @package     ActiveSync
 * @subpackage  Setup
 */
class ActiveSync_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /*
     * Rename columns that have more than 30 characters
     */
    function update_0(){
        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>allow_smime_encr_algor_negoti</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'allow_s_m_i_m_e_encryption_algorithm_negotiation');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>alphanum_device_password_req</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'alphanumeric_device_password_required');          
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {    
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>max_device_pass_fail_attempts</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'max_device_password_failed_attempts');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>max_email_htmlbody_trunc_size</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'max_email_h_t_m_l_body_truncation_size');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>max_inactiv_time_device_lock</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'max_inactivity_time_device_lock');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>min_device_pass_complex_char</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'min_device_password_complex_characters');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_encrypt_smime_messages</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'require_encrypted_s_m_i_m_e_messages');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_encrypt_smime_algorithm</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'require_encryption_s_m_i_m_e_algorithm');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }    

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_manual_sync_when_roaming</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'require_manual_sync_when_roaming');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_signed_smime_algorithm</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'require_encryption_s_m_i_m_e_algorithm');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_signed_smime_messages</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'require_signed_s_m_i_m_e_messages'); 
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>req_storage_card_encryption</name>
                    <type>boolean</type>
                </field>');
            
            $this->_backend->alterCol('acsync_policy', $declaration, 'require_storage_card_encryption');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }    

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>unapproved_in_rom_app_list</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'unapproved_in_r_o_m_application_list');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>allow_unsigned_install_packag</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'allow_unsigned_installation_packages');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }

        try {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>alpnumeric_device_pass_req</name>
                    <type>boolean</type>
                </field>');

            $this->_backend->alterCol('acsync_policy', $declaration, 'alphanumeric_device_password_required');
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        }
        $this->setTableVersion('acsync_policy', 3);
        $this->setApplicationVersion('ActiveSync', '7.1');
    }
}
