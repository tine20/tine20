<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Voipmanager updates for version 5.x
 *
 * @package     Voipmanager
 * @subpackage  Setup
 */
class Voipmanager_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * shorten some db fields
     * 
     * @return void
     */
    public function update_0()
    {
        $shortenFieldNames = array(
            'web_language_writable'        => 'web_language_w',
            "language_writable"            => 'language_w',
            "display_method_writable"      => 'display_method_w',
            "call_waiting_writable"        => 'call_waiting_w',
            "mwi_notification_writable"    => 'mwi_notification_w',
            "mwi_dialtone_writable"        => 'mwi_dialtone_w',
            "headset_device_writable"      => 'headset_device_w',
            "message_led_other_writable"   => 'message_led_other_w',
            "global_missed_counter_writable" => 'global_missed_counter_w',
            "scroll_outgoing_writable"     => 'scroll_outgoing_w',
            "show_local_line_writable"     => 'show_local_line_w',
            "show_call_status_writable"    => 'show_call_status_w',
        );
        
        foreach ($shortenFieldNames as $old => $new) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>' . $new . '</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>');
            $this->_backend->alterCol('snom_default_settings', $declaration, $old);
        }
        
        $this->setTableVersion('snom_default_settings', 2);
        
        $this->setApplicationVersion('Voipmanager', '5.1');
    }

    /**
     * replace enums
     * 
     * @return void
     */
    public function update_1()
    {
        $tables = array(
            'snom_location' => array(
                'version' => 2,
                'fields'  => array(
                    'update_policy' => array('default' => 'auto_update'),
                    'admin_mode' => array('default' => 'false'),
                    'webserver_type' => array('default' => 'https'),
                    'tone_scheme' => array(),
                )
            ),
            'snom_templates' => array(
                'version' => 2,
                'fields'  => array(
                    'model' => array('default' => 'snom300'),
                )
            ),
            'snom_phones' => array(
                'version' => 2,
                'fields'  => array(
                    'current_model' => array('default' => 'snom300'),
                    'redirect_event' => array('default' => 'none'),
                )
            ),
            'asterisk_sip_peers' => array(
                'version' => 2,
                'fields'  => array(
                    'dtmfmode' => array('default' => 'rfc2833'),
                    'insecure' => array('default' => 'no'),
                    'nat' => array('default' => 'no'),
                    'qualify' => array('default' => 'no'),
                    'type' => array('default' => 'friend'),
                    'cfi_mode' => array('default' => 'off'),
                    'cfb_mode' => array('default' => 'off'),
                    'cfd_mode' => array('default' => 'off'),
                )
            ),
            'snom_default_settings' => array(
                'version' => 3,
                'fields'  => array(
                    'web_language' => array(),
                    'language' => array(),
                    'display_method' => array(),
                    'mwi_notification' => array(),
                    'mwi_dialtone' => array(),
                    'headset_device' => array(),
                    'call_waiting' => array(),
                )
            ),
            'snom_phone_settings' => array(
                'version' => 2,
                'fields'  => array(
                    'web_language' => array(),
                    'language' => array(),
                    'display_method' => array(),
                    'mwi_notification' => array(),
                    'mwi_dialtone' => array(),
                    'headset_device' => array(),
                    'call_waiting' => array(),
                )
            ),
            'snom_phones_acl' => array(
                'version' => 2,
                'fields'  => array(
                    'account_type' => array('default' => 'user'),
                )
            ),
            'asterisk_redirects' => array(
                'version' => 2,
                'fields'  => array(
                    'cfi_mode' => array('default' => 'off'),
                    'cfb_mode' => array('default' => 'off'),
                    'cfd_mode' => array('default' => 'off'),
                )
            ),
        );
        
        foreach ($tables as $table => $data) {
            //if ($table === 'asterisk_redirects' && )
            foreach ($data['fields'] as $field => $fieldData) {
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $field . '</name>
                        <type>text</type>
                        <length>32</length>
                        ' . ((! empty($fieldData)) ? '<default>' . $fieldData['default'] . '</default><notnull>true</notnull>' : '') . '
                    </field>');
                try {
                    $this->_backend->alterCol($table, $declaration);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    if ($table === 'asterisk_redirects') {
                        // this table has been accidentally dropped in Voipmanager_Setup_Update_Release3::update_1();
                        $this->_addAsteriskRedirects();
                    } else {
                        throw $zdse;
                    }
                }
            }
            $this->setTableVersion($table, $data['version']);
        }
        
        $this->setApplicationVersion('Voipmanager', '5.2');
    }
    
    /**
     * add asterisk_redirects table
     */
    protected function _addAsteriskRedirects()
    {
        $tableDefinition = '
        <table>
            <name>asterisk_redirects</name>
            <engine>InnoDB</engine>
            <charset>utf8</charset>
            <version>2</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cfi_mode</name>
                    <type>text</type>
                    <length>32</length>
                    <default>off</default>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cfi_number</name>
                    <type>text</type>
                    <length>80</length>
                </field>
                <field>
                    <name>cfb_mode</name>
                    <type>text</type>
                    <length>32</length>
                    <default>off</default>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cfb_number</name>
                    <type>text</type>
                    <length>80</length>
                </field>
                <field>
                    <name>cfd_mode</name>
                    <type>text</type>
                    <length>32</length>
                    <default>off</default>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>cfd_number</name>
                    <type>text</type>
                    <length>80</length>
                </field>
                <field>
                    <name>cfd_time</name>
                    <type>integer</type>
                    <length>11</length>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>asterisk_redirect::id--asterisk_sip_peers::id</name>
                    <field>
                        <name>id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>asterisk_sip_peers</table>
                        <field>id</field>
                    </reference>
                    <ondelete>cascade</ondelete>
                    <onupdate>cascade</onupdate>
                </index>   
            </declaration>
        </table>';
        
        $table = Setup_Backend_Schema_Table_Factory::factory('String', $tableDefinition);
        $this->createTable('asterisk_redirects', $table, 'Voipmanager', 2);
    }

    /**
     * fix index asterisk_redirect::id--asterisk_sip_peers::id
     * 
     * @return void
     */
    public function update_2()
    {
        $this->_backend->dropForeignKey('asterisk_redirects', 'asterisk_redirect::id--asterisk_sip_peers::id');
        $declaration = new Setup_Backend_Schema_Index_Xml('
        <index>
            <name>asterisk_redirect::id--asterisk_sip_peers::id</name>
            <field>
                <name>id</name>
            </field>
            <foreign>true</foreign>
            <reference>
                <table>asterisk_sip_peers</table>
                <field>id</field>
                <ondelete>cascade</ondelete>
                <onupdate>cascade</onupdate>
            </reference>
        </index>');
        $this->_backend->addForeignKey('asterisk_redirects', $declaration);
        
        $this->setTableVersion('asterisk_redirects', 3);
        $this->setApplicationVersion('Voipmanager', '5.3');
    }

    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_3()
    {
        $this->setApplicationVersion('Voipmanager', '6.0');
    }
}
