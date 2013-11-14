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
        $this->validateTableVersion('snom_default_settings', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication</name>
                <type>boolean</type>
                <notnull>false</notnull>
                <default>NULL</default>
            </field>
        ');
        $this->_backend->addCol('snom_default_settings', $declaration);
        
        $this->setTableVersion('snom_default_settings', 4)
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication_w</name>
                <type>boolean</type>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('snom_default_settings', $declaration);
        
        $this->validateTableVersion('snom_phone_settings', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>pickup_indication</name>
                <type>boolean</type>
                <notnull>false</notnull>
                <default>NULL</default>
            </field>
        ');
        $this->_backend->addCol('snom_phone_settings', $declaration);
        
        $this->setTableVersion('snom_phone_settings', 3)
        
        $this->setApplicationVersion('Voipmanager', '8.1');
    }
}
