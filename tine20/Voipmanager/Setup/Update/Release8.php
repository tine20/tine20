<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Voipmanager updates for version 7.x
 *
 * @package     Voipmanager
 * @subpackage  Setup
 */
class Voipmanager_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
    * update to 8.1
    *
    * @return void
    */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication</name>
                <type>boolean</type>
                <notnull>false</notnull>
                <default>NULL</default>
            </field>
        ');
        $this->_backend->addCol('snom_default_settings', $declaration);
        
        $this->setTableVersion('snom_default_settings', 4);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication_w</name>
                <type>boolean</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('snom_default_settings', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication</name>
                <type>boolean</type>
                <notnull>false</notnull>
                <default>NULL</default>
            </field>
        ');
        $this->_backend->addCol('snom_phone_settings', $declaration);
        
        $this->setTableVersion('snom_phone_settings', 3);
        
        $this->setApplicationVersion('Voipmanager', '8.1');
    }
    
    /**
     * allow setting of canreinvite / directmedia
     */
    public function update_1()
    {
        $field = '<field>
            <name>canreinvite</name>
            <type>text</type>
            <default>yes</default>
            <length>10</length>
            <notnull>true</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('asterisk_sip_peers', $declaration);
    
        // default is canreinvite=yes, but the behavior is not wanted, if the client is behind nat
        $quotedTableName = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "asterisk_sip_peers");
        $sql = "UPDATE " . $quotedTableName . " SET " . $this->_db->quoteIdentifier('canreinvite') . " = 'no' WHERE " . $this->_db->quoteIdentifier('nat') . " = 'yes';";
        $this->_db->query($sql);
        
        $this->setTableVersion('asterisk_sip_peers', '3');
        $this->setApplicationVersion('Voipmanager', '8.2');
    }
}
