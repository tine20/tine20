<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Addressbook_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lon</name>
                <type>float</type>
            </field>');
        $this->_backend->addCol('addressbook', $declaration);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>lat</name>
                <type>float</type>
            </field>');
        $this->_backend->addCol('addressbook', $declaration);
        
        $this->setApplicationVersion('Addressbook', '2.1');
    }
    
    /**
     * update to 3.0
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Addressbook', '3.0');
    }
}
