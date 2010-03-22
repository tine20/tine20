<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class ActiveSync_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * add filter columns
     * @return void
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>contactfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>calendarfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>taskfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>emailfilter</name>
                <type>text</type>
                <length>64</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('acsync_device', $declaration);
        
        $this->setApplicationVersion('ActiveSync', '3.1');
    }
}
