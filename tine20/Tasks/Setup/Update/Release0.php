<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Release0.php 9082 2009-07-04 18:26:46Z l.kneschke@metaways.de $
 */

class Tasks_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_1()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('class', $declaration, 'created_by');
        $this->_backend->alterCol('tasks', $declaration, 'created_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('tasks_status', $declaration, 'created_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('class', $declaration, 'last_modified_by');
        $this->_backend->alterCol('tasks_status', $declaration, 'last_modified_by');
        $this->_backend->alterCol('tasks', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('class', $declaration, 'deleted_by');
        $this->_backend->alterCol('tasks_status', $declaration, 'deleted_by');
        $this->_backend->alterCol('tasks', $declaration, 'deleted_by');
        
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>organizer</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('tasks', $declaration, 'organizer');
        
        $this->setApplicationVersion('Tasks', '0.2');
    }
}
