<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * 
     * @see 0009152: saving of record fails because of too many relations
     */
    public function update_0()
    {
        $valueFields = array('old_value', 'new_value');
        foreach ($valueFields as $field) {
            
            // check schema, only change if type == text
            $typeMapping = $this->_backend->getTypeMapping('text');
            $schema = Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'timemachine_modlog', $this->_backend->getDb());
            
            if ($schema[$field]['DATA_TYPE'] === $typeMapping['defaultType']) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Old column type (' . $schema[$field]['DATA_TYPE'] . ') is going to be altered to clob');
                
                $declaration = new Setup_Backend_Schema_Field_Xml('
                    <field>
                        <name>' . $field . '</name>
                        <type>clob</type>
                    </field>
                ');
            
                $this->_backend->alterCol('timemachine_modlog', $declaration);
            }
        }
        $this->setTableVersion('timemachine_modlog', '3');
        $this->setApplicationVersion('Tinebase', '8.1');
    }

    /**
     * update to 8.2
     * 
     * @see 0009644: remove user registration
     */
    public function update_1()
    {
        if ($this->_backend->tableExists('registrations')) {
            $this->dropTable('registrations');
        }
        
        if ($this->_backend->tableExists('registration_invitation')) {
            $this->dropTable('registration_invitation');
        }
        
        $this->setApplicationVersion('Tinebase', '8.2');
    }
    
    /**
     * - add filter acl
     * - update current filter (add default grants: user for personal favorites, Admin group for shared favorites)
     * 
     * @see 0009610: shared favorites acl
     */
    public function update_2()
    {
        $this->_addFilterAclTable();
        $this->_addGrantsToExistingFilters();
        $this->setApplicationVersion('Tinebase', '8.3');
    }
    
    /**
     * add filter acl table
     */
    protected function _addFilterAclTable()
    {
        $xml = $declaration = new Setup_Backend_Schema_Table_Xml('<table>
            <name>filter_acl</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_type</name>
                    <type>text</type>
                    <length>32</length>
                    <default>user</default>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>account_grant</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <index>
                    <name>record_id-account-type-account_id-account_grant</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                    <field>
                        <name>account_grant</name>
                    </field>
                </index>
                <index>
                    <name>id-account_type-account_id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>account_type</name>
                    </field>
                    <field>
                        <name>account_id</name>
                    </field>
                </index>
                <index>
                    <name>filter_acl::record_id--filter::id</name>
                    <field>
                        <name>record_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>filter</table>
                        <field>id</field>
                        <ondelete>cascade</ondelete>
                    </reference>
                </index>
            </declaration>
        </table>');
        
        $this->createTable('filter_acl', $declaration);
    }
    
    /**
     * add default grants to existing filters
     */
    protected function _addGrantsToExistingFilters()
    {
        $pfBackend = new Tinebase_PersistentFilter_Backend_Sql();
        $filters = $pfBackend->getAll();
        $pfGrantsBackend = new Tinebase_Backend_Sql_Grants(array(
            'modelName' => 'Tinebase_Model_PersistentFilterGrant',
            'tableName' => 'filter_acl'
        ));
        $pfGrantsBackend->getGrantsForRecords($filters);
        
        foreach ($filters as $filter) {
            if (count($filter->grants) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Filter ' . $filter->name . ' already has grants.');
                continue;
            }
            $filter->grants = new Tinebase_Record_RecordSet('Tinebase_Model_PersistentFilterGrant');
            if ($filter->isPersonal()) {
                // personal filter -> user gets all grants
                $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                $accountId = $filter->account_id;
            } else {
                // shared filter -> anyone or admin group (if ANYONE_ACCOUNT_DISABLED) gets all grants
                if (Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
                    $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP;
                    $accountId = Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId();
                } else {
                    $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                    $accountId = 0;
                }
            }
            $grant = new Tinebase_Model_PersistentFilterGrant(array(
                'account_type' => $accountType,
                'account_id'   => $accountId,
                'record_id'    => $filter->getId(),
            ));
            $grant->sanitizeAccountIdAndFillWithAllGrants();
            $filter->grants->addRecord($grant);
            Tinebase_PersistentFilter::getInstance()->setGrants($filter);
        }
    }
}
