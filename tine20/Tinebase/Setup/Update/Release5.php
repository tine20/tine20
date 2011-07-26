<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     * - enum -> text
     */
    public function update_0()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <default>enabled</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('applications', $declaration);
        $this->setTableVersion('applications', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>visibility</name>
                <type>text</type>
                <length>32</length>
                <default>displayed</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('groups', $declaration);
        $this->setTableVersion('groups', 4);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>visibility</name>
                <type>text</type>
                <length>32</length>
                <default>displayed</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('accounts', $declaration);
        $this->setTableVersion('accounts', 9);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
                <value>activated</value>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('registrations', $declaration);
        $this->setTableVersion('registrations', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <default>personal</default>
                <notnull>false</notnull>
            </field>');
        $this->_backend->alterCol('container', $declaration);
        $this->setTableVersion('container', 4);

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('container_acl', $declaration);
        $this->setTableVersion('container_acl', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>own_degree</name>
                <type>text</type>
                <length>32</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('relations', $declaration);
        $this->setTableVersion('relations', 6);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('tags', $declaration);
        $this->setTableVersion('tags', 3);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('tags_acl', $declaration);
        $this->setTableVersion('tags_acl', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('role_accounts', $declaration);
        $this->setTableVersion('role_accounts', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('preferences', $declaration);
        $this->setTableVersion('preferences', 7);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_type</name>
                <type>text</type>
                <length>32</length>
                <default>user</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('customfield_acl', $declaration);
        $this->setTableVersion('customfield_acl', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>type</name>
                <type>text</type>
                <length>32</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('importexport_definition', $declaration);
        $this->setTableVersion('importexport_definition', 5);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>sent_status</name>
                <type>text</type>
                <length>32</length>
                <default>pending</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('alarm', $declaration);
        $this->setTableVersion('alarm', 2);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>32</length>
            </field>');
        $this->_backend->alterCol('async_job', $declaration);
        $this->setTableVersion('async_job', 2);
        
        $this->setApplicationVersion('Tinebase', '5.1');
    }
}
