<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Voipmanager_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * rename username to defaultuser
     * add auto to dtmfmode enum
     * add column regserver, useragent and lastms
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>dtmfmode</name>
                <type>enum</type>
                <value>inband</value>
                <value>info</value>
                <value>rfc2833</value>
                <value>auto</value>
                <default>rfc2833</default>
            </field>');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>defaultuser</name>
                <type>text</type>
                <length>80</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration, 'username');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>regserver</name>
                <type>text</type>
                <length>254</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>useragent</name>
                <type>text</type>
                <length>254</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lastms</name>
                <type>integer</type>
                <length>11</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '2.1');
    }    
}
