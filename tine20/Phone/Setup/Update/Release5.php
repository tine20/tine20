<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Phone_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>direction</name>
                <type>text</type>
                <length>32</length>
                <default>in</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('phone_callhistory', $declaration);
        
        $this->setTableVersion('phone_callhistory', 2);
        $this->setApplicationVersion('Phone', '5.1');
    }
    
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_1()
    {
        $this->setApplicationVersion('Phone', '6.0');
    }
}
