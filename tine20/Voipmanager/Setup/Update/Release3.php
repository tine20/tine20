<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

class Voipmanager_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * change type of id to varchar
     * drop column sippeer_id
     * add foreign key asterisk_redirects::id--asterisk_sip_peers::id
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '3.1');
    }

    /**
     * change type of id to varchar
     * drop column sippeer_id
     * add foreign key asterisk_redirects::id--asterisk_sip_peers::id
     */
    public function update_1()
    {
        $this->_backend->dropTable('asterisk_redirects');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfi_mode</name>
                <type>enum</type>
                <value>off</value>
                <value>number</value>
                <value>voicemail</value>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfi_number</name>
                <type>text</type>
                <length>80</length>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfb_mode</name>
                <type>enum</type>
                <value>off</value>
                <value>number</value>
                <value>voicemail</value>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfb_number</name>
                <type>text</type>
                <length>80</length>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfd_mode</name>
                <type>enum</type>
                <value>off</value>
                <value>number</value>
                <value>voicemail</value>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfd_number</name>
                <type>text</type>
                <length>80</length>
            </field>
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cfd_time</name>
                <type>integer</type>
                <length>11</length>
            </field>                
        ');
        $this->_backend->addCol('asterisk_sip_peers', $declaration);
        
        $this->setApplicationVersion('Voipmanager', '3.2');
    }
}
