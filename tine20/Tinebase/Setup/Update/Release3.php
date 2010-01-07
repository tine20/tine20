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

class Tinebase_Setup_Update_Release3 extends Setup_Update_Abstract
{    
    /**
     * update to 3.1
     * - add value_search option field to customfield_config
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>value_search</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('customfield_config', $declaration);
        
        $this->setTableVersion('customfield_config', '4');
        $this->setApplicationVersion('Tinebase', '3.1');
    }    

    /**
     * update to 3.2
     * - add personal_only field to preference
     * - remove all admin/default prefs with this setting
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>personal_only</name>
                <type>boolean</type>
            </field>');
        $this->_backend->addCol('preferences', $declaration);
        
        $this->setTableVersion('preferences', '5');
        
        // remove all personal only prefs for anyone
        $this->_db->query("DELETE FROM " . SQL_TABLE_PREFIX . "preferences WHERE account_type LIKE 'anyone' AND name IN ('defaultCalendar', 'defaultAddressbook')");
        
        $this->setApplicationVersion('Tinebase', '3.2');
    }    
}
