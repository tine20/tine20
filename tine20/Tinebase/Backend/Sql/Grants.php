<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend for records with grants
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Grants extends Tinebase_Backend_Sql
{
    /**
     * get grants for records
     * 
     * @param Tinebase_Record_RecordSet $records
     */
    public function getGrantsForRecords(Tinebase_Record_RecordSet $records)
    {
        $recordIds = $records->getArrayOfIds();
        if (empty($recordIds)) {
            return;
        }
        
        $select = $this->_getAclSelectByRecordIds($recordIds)
            ->group(array('record_id', 'account_type', 'account_id'));
        
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . $select);
        
        $stmt = $this->_db->query($select);

        $grantsData = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' grantsData: ' . print_r($grantsData, true));

        foreach ($grantsData as $grantData) {
            $givenGrants = explode(',', $grantData['account_grants']);
            foreach ($givenGrants as $grant) {
                $grantData[$grant] = TRUE;
            }
            
            $recordGrant = new $this->_modelName($grantData, true);
            unset($recordGrant->account_grant);
            
            $record = $records->getById($recordGrant->record_id);
            $records->removeRecord($record);
            if (! $record->grants instanceof Tinebase_Record_RecordSet) {
                $record->grants = new Tinebase_Record_RecordSet($this->_modelName);
            }
            $record->grants->addRecord($recordGrant);

            // NOTICE: this is strange - we have to remove the record and add it
            //   again to make sure that grants are updated ...
            //   maybe we should add a "replace" method?
            $records->addRecord($record);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Records with grants: ' . print_r($records->toArray(), true));
    }
    
    /**
     * get select with acl (grants) by record
     * 
     * @param string|array $recordId
     * @return Zend_Db_Select
     */
    protected function _getAclSelectByRecordIds($recordIds)
    {
         $select = $this->_db->select()
            ->from(
                array($this->getTableName() => SQL_TABLE_PREFIX . $this->getTableName()),
                array('*', 'account_grants' => $this->_dbCommand->getAggregate('account_grant'))
            )
            ->where("{$this->_db->quoteIdentifier('record_id')} IN (?)", (array)$recordIds);
         return $select;
    }
}
