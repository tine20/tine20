<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
}
