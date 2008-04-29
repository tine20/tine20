<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Crm_Setup_Update_Release0 extends Setup_Update_Abstract
{
    public function update_0()
    {
        // just show how it works
        $tableDEF = array(
        'XmlString' =>
                '<table>
                    <name>test_table</name>
                    <version>1</version>
                    <declaration>
                        <field>
                            <name>id</name>
                            <type>integer</type>
                            <autoincrement>true</autoincrement>
                        </field>
                        <field>
                            <name>name</name>
                            <type>text</type>
                            <length>128</length>
                            <notnull>true</notnull>
                        </field>
                        <index>
                            <name>id</name>
                            <primary>true</primary>
                            <field>
                                <name>id</name>
                            </field>
                        </index>
                    </declaration>
                </table>'
        );
                        
        $this->_backend->createTable($tableDEF);
        $this->_backend->dropTable('test_table');
    }

    public function update_1()
    {

        $this->validateTableVersion('application_rights', '1');
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'account_type';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'true';
        $declaration->value     = array('anyone', 'account', 'group');
        
        $this->_backend->addCol('application_rights', $declaration);
        
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'right';
        $declaration->type      = 'text';
        $declaration->length    = 64;
        $declaration->notnull   = 'true';
        
        $this->_backend->alterCol('application_rights', $declaration);
        
        $rightsTable = new Tinebase_Db_Table(array('name' =>  'application_rights'));
        
        
        $data = array(
            'account_type' => 'anyone'
        );
        $where = array(
            'account_id IS NULL',
            'group_id IS NULL'
        );
        $rightsTable->update($data, $where);

        
        $data = array(
            'account_type' => 'account'
        );
        $where = array(
            'account_id IS NOT NULL',
            'group_id IS NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_type' => 'group'
        );
        $where = array(
            'account_id IS NULL',
            'group_id IS NOT NULL'
        );
        $rightsTable->update($data, $where);
        
        
        $data = array(
            'account_id' => new Zend_Db_Expr('group_id'),
        );
        $where = array(
            $rightsTable->getAdapter()->quoteInto('account_type = ?', 'group'),
        );
        $rightsTable->update($data, $where);

        $this->_backend->dropIndex('application_rights', 'account_id-group_id-application_id-right');
        
        $index = new StdClass();
        $index->name = 'account_id-account_type-application_id-right';
        $index->unique = 'true';
        $index->field = array();
        
        $field = new StdClass();
        $field->name = 'account_id';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'account_type';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'application_id';
        $index->field[] = $field;
        
        $field = new StdClass();
        $field->name = 'right';
        $index->field[] = $field;
        
        $this->_backend->addIndex( 'application_rights', $index);
        
        $this->_backend->dropCol('application_rights', 'group_id');
        
        $this->setTableVersion('application_rights', '2');
        $this->setApplicationVersion('Tinebase', '0.2');
    }
}