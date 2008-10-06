<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Addressbook_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * this function does nothing. It's from the dark ages without setup being functional
     */    
    public function update_1()
    {
        $this->validateTableVersion('addressbook', '1');        
        
        $this->setApplicationVersion('Addressbook', '0.2');
    }
    
    /**
     * updates what???
     * 
     * @todo add changed fields
     */    
    public function update_2()
    {
        $this->validateTableVersion('addressbook', '1');        
        
        $this->setTableVersion('addressbook', '2');
        $this->setApplicationVersion('Addressbook', '0.3');
    }
    
    /**
     * correct modlog field definitions
     */    
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>integer</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>');
        $this->_backend->alterCol('addressbook', $declaration);
        
        $this->setApplicationVersion('Addressbook', '0.4');
    }
                
    /**
     * add salutation field
     */    
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>salutation</name>
                <type>text</type>
                <length>32</length>
            </field>');
        $this->_backend->addCol('addressbook', $declaration);
        
        $this->setApplicationVersion('Addressbook', '0.5');
    }
}
