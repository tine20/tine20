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
    
    /**
     * change all fields which store account ids from integer to string
     * 
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration, 'created_by');
        $this->_backend->alterCol('timetracker_timesheet', $declaration, 'created_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration, 'last_modified_by');
        $this->_backend->alterCol('timetracker_timesheet', $declaration, 'last_modified_by');

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>');
        $this->_backend->alterCol('timetracker_timeaccount', $declaration, 'deleted_by');
        $this->_backend->alterCol('timetracker_timesheet', $declaration, 'deleted_by');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>account_id</name>
                <type>text</type>
                <length>40</length>
                <notnull>true</notnull>
            </field>');
        $this->_backend->alterCol('timetracker_timesheet', $declaration, 'account_id');
        
        $this->setApplicationVersion('Timetracker', '0.5');
    }
    
    /**
     * update from 0.5 to 2.0
     * - copy entries from timetracker_timesheet_custom to customfield table
     * - drop timetracker_timesheet_custom table
     */
    public function update_5()
    {
        // get all timetracker/timesheet custom fields
        $customfields = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Timetracker'),
            'Timetracker_Model_Timesheet'
        );
        
        if (count($customfields) > 0) {
            
            $customfields->addIndices(array('name'));
            
            // get all custom field values
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'timetracker_timesheet_custom')
                ->order('name ASC');
            $stmt = $this->_db->query($select);
            $queryResult = $stmt->fetchAll();
    
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($queryResult, TRUE));
            
            // insert values into customfield table
            $cfValueBackend = new Tinebase_Backend_Sql('Tinebase_Model_CustomField_Value', 'customfield');
            foreach ($queryResult as $row) {
                if (! isset($customfield) || $customfield->name != $row['name']) {
                    $customfield = $customfields->filter('name', $row['name'])->getFirstRecord();
                }
                $cfValue = new Tinebase_Model_CustomField_Value(array(
                    'record_id'         => $row['record_id'],
                    'customfield_id'    => $customfield->getId(),
                    'value'             => $row['value'],
                ));
                $cfValueBackend->create($cfValue);
            }
        }
        
        // drop obsolete table
        $this->dropTable('timetracker_timesheet_custom');
        
        $this->setApplicationVersion('Timetracker', '2.0');
    }
}
