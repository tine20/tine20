<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

class Timetracker_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add is_billable to timeaccounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
                    <name>is_billable</name>
                    <type>boolean</type>
                    <default>true</default>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setApplicationVersion('Timetracker', '0.2');
    }

    /**
     * update function 2
     * - add status to timeaccounts
     *
     */    
    public function update_2()
    {
        $field = '<field>
            <name>status</name>
            <type>enum</type>
            <value>not yet billed</value>
            <value>to bill</value>
            <value>billed</value>
            <notnull>true</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setApplicationVersion('Timetracker', '0.3');
    }

    /**
     * update function 3
     * - add billed_in to timeaccounts
     *
     */    
    public function update_3()
    {
        $field = '<field>
            <name>billed_in</name>
            <type>text</type>
            <length>256</length>
            <notnull>false</notnull>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('timetracker_timeaccount', $declaration);
        
        $this->setApplicationVersion('Timetracker', '0.4');
    }
}
