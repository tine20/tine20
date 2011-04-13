<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class Courses_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update function 1
     * - add fileserver (access) to timeaccounts
     *
     */    
    public function update_1()
    {
        $field = '<field>
            <name>fileserver</name>
            <type>boolean</type>
            <default>false</default>
        </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('courses', $declaration);
        
        $this->setApplicationVersion('Courses', '0.2');
    }
    
    /**
     * update function 2
     * - add fileserver (access) to timeaccounts
     *
     */    
    public function update_2()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>group_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('courses', $declaration, 'group_id');

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('courses', $declaration, 'created_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('courses', $declaration, 'last_modified_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('courses', $declaration, 'deleted_by');
        
        $this->setTableVersion('courses', '3');
        $this->setApplicationVersion('Courses', '0.3');
    }
    
    /**
     * update to 2.0
     * @return void
     */
    public function update_3()
    {
        $this->setApplicationVersion('Courses', '2.0');
    }
}
