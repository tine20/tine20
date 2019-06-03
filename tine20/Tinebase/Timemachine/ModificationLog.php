<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ModificationLog tracks and supplies the logging of modifications on a field 
 * basis of records. It's an generic approach which could be usesed by any 
 * application. Besides, providing a logbook, the real power of ModificationLog 
 * depends the combination with the Timemachine.
 * 
 * ModificationLog logges differences of complete fields. This is in contrast to
 * changetracking of other products which have sub field resolution. As in
 * general, the sub field approach offers most felxibility, the complete field 
 * solution is an adequate compromise for usage and performace.
 * 
 * ModificationLog is used by Tinebase_Timemachine_Abstract. If an application
 * backened extends Tinebase_Timemachine_Abstract, it MUST use 
 * Tinebase_Timemachine_ModificationLog to track modifications
 * 
 * NOTE: Maximum time resolution is one second. If there are more than one
 * modifications in a second, they are distinguished by the accounts which made
 * the modifications and a autoincement key of the underlaying database table.
 * NOTE: Timespans are allways defined, with the beginning point excluded and
 * the end point included. Mathematical: (_from, _until]
 * 
 * @package Tinebase
 * @subpackage Timemachine
 * 
 * @todo Add registry for logbook starttime and methods to throw away logbook 
 *       entries. Throw exceptions when times are requested which are not in the 
 *       log anymore!
 * @todo refactor this to use generic sql backend + remove Tinebase_Db_Table usage
 */
class Tinebase_Timemachine_ModificationLog implements Tinebase_Controller_Interface
{
    const CREATED = 'created';
    const DELETED = 'deleted';
    const UPDATED = 'updated';

    /**
     * Tablename SQL_TABLE_PREFIX . timemachine_modificationlog
     *
     * @var string
     */
    protected $_tablename = 'timemachine_modlog';
    
    /**
     * Holds table instance for timemachine_history table
     *
     * @var Tinebase_Db_Table
     */
    protected $_table = NULL;
    
    /**
     * holds names of meta properties in record
     * 
     * @var array
     * 
     * @see 0007494: add changes in notes to modlog/history
     */
    protected $_metaProperties = array(
        'created_by',
        'creation_time',
        'last_modified_by',
        'last_modified_time',
        //do NOT add is_deleted!
        //'is_deleted',
        'deleted_time',
        'deleted_by',
        'seq',
    );
    
    /**
     * the sql backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Timemachine_ModificationLog
     */
    private static $instance = NULL;

    /**
     * holds the applicationId of the current context temporarily.
     *
     * @var string
     */
    protected $_applicationId = NULL;

    /**
     * if set, all newly created modlogs will have this external instance id. this is used during applying replication logs
     *
     * @var string
     */
    protected $_externalInstanceId = NULL;

    protected $_readModificationLogFromMasterLockId = null;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Timemachine_ModificationLog
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Timemachine_ModificationLog();
        }
        
        return self::$instance;
    }
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_tablename = SQL_TABLE_PREFIX . $this->_tablename;
        
        $this->_table = new Tinebase_Db_Table(array('name' => $this->_tablename));
        $this->_table->setRowClass('Tinebase_Model_ModificationLog');
        
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_ModificationLog',
            'tableName' => 'timemachine_modlog',
        ));
    }

    /**
     * clean timemachine_modlog for records that have been pruned (not deleted!)
     *
     * TODO if replication is on, we need to keep the "deleted" / "pruned" message in the modlog
     */
    public function clean()
    {
        $filter = new Tinebase_Model_Filter_FilterGroup();
        $pagination = new Tinebase_Model_Pagination();
        $pagination->limit = 10000;
        $pagination->sort = 'id';

        $totalCount = 0;

        while ( ($recordSet = $this->_backend->search($filter, $pagination)) && $recordSet->count() > 0 ) {
            $filter = new Tinebase_Model_Filter_FilterGroup();
            $pagination->start += $pagination->limit;
            $models = array();

            /** @var Tinebase_Model_ModificationLog $modlog */
            foreach($recordSet as $modlog) {
                if (Tinebase_Model_Container::class === $modlog->record_type) {
                    $models[$modlog->record_type][$modlog->application_id][$modlog->record_id][] = $modlog->id;
                } else {
                    $models[$modlog->record_type][0][$modlog->record_id][] = $modlog->id;
                }
            }

            foreach($models as $model => &$ids) {

                if (Tinebase_Model_Tree_Node::class === $model) {
                    continue;
                }

                $app = null;
                $appNotFound = false;

                try {
                    $app = Tinebase_Core::getApplicationInstance($model, '', true);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $appNotFound = true;
                }

                if (!$appNotFound) {

                    if ($app instanceof Tinebase_Container)
                    {
                        $backend = $app;

                    } else {
                        if (!$app instanceof Tinebase_Controller_Record_Abstract) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' model: ' . $model . ' controller: ' . get_class($app) . ' not an instance of Tinebase_Controller_Record_Abstract');
                            continue;
                        }

                        $backend = $app->getBackend();
                    }

                    if (!$backend instanceof Tinebase_Backend_Interface) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' model: ' . $model . ' backend: ' . get_class($backend) . ' not an instance of Tinebase_Backend_Interface');
                        continue;
                    }

                    /** @var Tinebase_Record_Abstract $record */
                    $record = new $model(null, true);

                    foreach ($ids as $key => &$ids2) {
                        /** @var Tinebase_Model_Filter_FilterGroup $idFilter */
                        $idFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            $model,
                            array(),
                            '',
                            array('ignoreAcl' => true)
                        );
                        $idFilter->addFilter(new Tinebase_Model_Filter_Id(array(
                            'field' => $record->getIdProperty(),
                            'operator' => 'in',
                            'value' => array_keys($ids2)
                        )));
                        if (Tinebase_Model_Container::class === $model) {
                            $idFilter->addFilter(new Tinebase_Model_Filter_Id(array(
                                'field' => 'application_id',
                                'operator' => 'equals',
                                'value' => $key
                            )));
                        }

                        // to work around Tinebase_Container, we just send one more true parameter, will be ignored by all real backends, only taken into account by Tinebase_Container
                        $existingIds = $backend->search($idFilter, null, true, true);

                        if (!is_array($existingIds)) {
                            throw new Exception('search for model: ' . $model . ' returned not an array!');
                        }
                        foreach ($existingIds as $id) {
                            unset($ids2[$id]);
                        }
                    }
                    unset($ids2);
                }

                foreach ($ids as $ids2) {
                    if (count($ids2) > 0) {
                        $toDelete = array();
                        foreach ($ids2 as $idArrays) {
                            foreach ($idArrays as $id) {
                                $toDelete[$id] = true;
                            }
                        }

                        $toDelete = array_keys($toDelete);

                        $this->_backend->delete($toDelete);
                        $totalCount += count($toDelete);
                    }
                }
            }
            unset($ids);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' deleted ' . $totalCount . ' modlogs records');

        return $totalCount;
    }
    
    /**
     * Returns modification of a given record in a given timespan
     * 
     * @param string $_application application of given identifier  
     * @param string $_id identifier to retrieve modification log for
     * @param string $_type 
     * @param string $_backend 
     * @param Tinebase_DateTime $_from beginning point of timespan, excluding point itself
     * @param Tinebase_DateTime $_until end point of timespan, including point itself
     * @param int $_modifierId optional
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     * 
     * @todo use backend search() + Tinebase_Model_ModificationLogFilter
     */
    public function getModifications($_application, $_id, $_type = NULL, $_backend = 'Sql', Tinebase_DateTime $_from = NULL, Tinebase_DateTime $_until = NULL, $_modifierId = NULL, $_fromInstanceId = NULL)
    {
        $id = ($_id instanceof Tinebase_Record_Interface) ? $_id->getId() : $_id;
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        $isoDef = 'Y-m-d\TH:i:s';
        
        $db = $this->_table->getAdapter();
        $select = $db->select()
            ->from($this->_tablename)
            ->order('instance_seq ASC')
            ->where($db->quoteInto($db->quoteIdentifier('application_id') . ' = ?', $application->id));

        if (null !== $_id) {
            $select->where($db->quoteInto($db->quoteIdentifier('record_id') . ' = ?', $id));
        }
        
        if ($_from) {
            $select->where($db->quoteInto($db->quoteIdentifier('modification_time') . ' > ?', $_from->toString($isoDef)));
        }
        
        if ($_until) {
            $select->where($db->quoteInto($db->quoteIdentifier('modification_time') . ' <= ?', $_until->toString($isoDef)));
        }
        
        if ($_type) {
            $select->where($db->quoteInto($db->quoteIdentifier('record_type') . ' LIKE ?', $_type));
        }
        
        if ($_backend) {
            $select->where($db->quoteInto($db->quoteIdentifier('record_backend') . ' LIKE ?', $_backend));
        }
        
        if ($_modifierId) {
            $select->where($db->quoteInto($db->quoteIdentifier('modification_account') . ' = ?', $_modifierId));
        }

        if ($_fromInstanceId) {
            $select->where($db->quoteInto($db->quoteIdentifier('instance_seq') . ' >= ?', $_fromInstanceId));
        }

        $stmt = $db->query($select);
        $resultArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
       
        $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', $resultArray);
        return $modifications;
    }

    /**
     * get modifications by seq
     *
     * @param string $applicationId
     * @param Tinebase_Record_Interface $newRecord
     * @param integer $currentSeq
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     */
    public function getModificationsBySeq($applicationId, Tinebase_Record_Interface $newRecord, $currentSeq)
    {
        $filter = new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'seq',            'operator' => 'greater', 'value' => $newRecord->seq),
            array('field' => 'seq',            'operator' => 'less',    'value' => $currentSeq + 1),
            array('field' => 'record_type',    'operator' => 'equals',  'value' => get_class($newRecord)),
            array('field' => 'record_id',      'operator' => 'equals',  'value' => $newRecord->getId()),
            array('field' => 'application_id', 'operator' => 'equals',  'value' => $applicationId),
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'sort' => 'seq'
        ));
        
        return $this->_backend->search($filter, $paging);
    }

    /**
     * get modifications for replication (instance_id == TinebaseId) by instance seq
     *
     * @param integer $currentSeq
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     */
    public function getReplicationModificationsByInstanceSeq($currentSeq, $limit = 100)
    {
        $filter = new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'instance_id',  'operator' => 'equals',  'value' => Tinebase_Core::getTinebaseId()),
            array('field' => 'instance_seq', 'operator' => 'greater', 'value' => $currentSeq)
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'limit' => $limit,
            'sort'  => 'instance_seq'
        ));

        return $this->_backend->search($filter, $paging);
    }

    /**
     * returns the maximum instance_seq of entries that should be replicated
     *
     * @return int
     */
    public function getMaxInstanceSeq()
    {
        $db = $this->_table->getAdapter();
        $select = $db->select()
            ->from($this->_tablename, new Zend_Db_Expr('MAX(' . $db->quoteIdentifier('instance_seq') . ')'))
            ->where($db->quoteInto($db->quoteIdentifier('instance_id') . ' = ?', Tinebase_Core::getTinebaseId()));

        $stmt = $db->query($select);
        $resultArray = $stmt->fetchAll(Zend_Db::FETCH_NUM);

        if (count($resultArray) === 0) {
            return 0;
        }

        return intval($resultArray[0][0]);
    }
    
    /**
     * Computes effective difference from a set of modifications
     *
     * TODO check this claim re modified_from
     * TODO activate and rewrite test
     *
     * If a attribute got changed more than once, the returned diff has all
     * properties of the last change to the attribute, besides the 
     * 'modified_from', which holds the modified_from of the first change.
     * 
     * @param Tinebase_Record_RecordSet $modifications
     * @return Tinebase_Record_Diff differences
     */
    public function computeDiff(Tinebase_Record_RecordSet $modifications)
    {
        $diff = array();
        $oldData = array();
        /** @var Tinebase_Model_ModificationLog $modification */
        foreach ($modifications as $modification) {
            $modified_attribute = $modification->modified_attribute;

            // legacy code
            if (!empty($modified_attribute)) {
                if (!array_key_exists($modified_attribute, $diff)) {
                    $oldData[$modified_attribute] = $modification->old_value;
                }
                $diff[$modified_attribute] = $modification->new_value;

            // new modificationlog implementation
            } else {
                $tmpDiff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                if (is_array($tmpDiff->diff)) {
                    foreach ($tmpDiff->diff as $key => $value) {
                        if (!array_key_exists($key, $diff)) {
                            $oldData[$key] = $tmpDiff->oldData[$key];
                        }
                        $diff[$key] = $value;
                    }
                }
            }
        }
        $result = new Tinebase_Record_Diff();
        $result->diff = $diff;
        $result->oldData = $oldData;
        return $result;
    }
    
    /**
     * Returns a single logbook entry identified by an logbook identifier
     * 
     * @param   string $_id
     * @return  Tinebase_Model_ModificationLog
     * @throws  Tinebase_Exception_NotFound
     *
    public function getModification($_id)
    {
        $db = $this->_table->getAdapter();
        $stmt = $db->query($db->select()
           ->from($this->_tablename)
           ->where($this->_table->getAdapter()->quoteInto($db->quoteIdentifier('id') . ' = ?', $_id))
        );
        $RawLogEntry = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (empty($RawLogEntry)) {
            throw new Tinebase_Exception_NotFound("Modification Log with id: $_id not found!");
        }
        return new Tinebase_Model_ModificationLog($RawLogEntry[0], true);
    }*/

    /**
     * Saves a logbook record
     *
     * @param Tinebase_Model_ModificationLog $modification
     * @return string id
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_ConcurrencyConflict
     * @throws Zend_Db_Statement_Exception
     */
    public function setModification(Tinebase_Model_ModificationLog $modification)
    {
        $modification->isValid(TRUE);
        
        $id = $modification->generateUID();
        $modification->setId($id);
        $modification->convertDates = true;

        // mainly if we are applying replication modlogs on the slave, we set the masters instance id here
        if (null !== $this->_externalInstanceId) {
            $modification->instance_id = $this->_externalInstanceId;
        }

        $modificationArray = $modification->toArray();
        if (is_array($modificationArray['new_value'])) {
            throw new Tinebase_Exception_Record_Validation("New value is an array! \n" . print_r($modificationArray['new_value'], true));
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . " Inserting modlog: " . print_r($modificationArray, TRUE));
        try {
            $this->_table->insert($modificationArray);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                $zdse->getMessage() . ' ' . print_r($modification->toArray(), TRUE));
            
            // check if unique key constraint failed
            $filter = new Tinebase_Model_ModificationLogFilter(array(
                array('field' => 'seq',                'operator' => 'equals',  'value' => $modification->seq),
                array('field' => 'record_type',        'operator' => 'equals',  'value' => $modification->record_type),
                array('field' => 'record_id',          'operator' => 'equals',  'value' => $modification->record_id),
                array('field' => 'modified_attribute', 'operator' => 'equals',  'value' => $modification->modified_attribute),
            ));
            $result = $this->_backend->search($filter);
            if (count($result) > 0) {
                throw new Tinebase_Exception_ConcurrencyConflict('Seq ' . $modification->seq . ' for record ' . $modification->record_id . ' already exists');
            } else {
                throw $zdse;
            }
        }
        
        return $id;
    }
    
    /**
     * merges changes made to local storage on concurrent updates into the new record 
     *
     * @param string $applicationId
     * @param  Tinebase_Record_Interface $newRecord record from user data
     * @param  Tinebase_Record_Interface $curRecord record from storage
     * @return Tinebase_Record_Diff with resolved concurrent updates
     * @throws Tinebase_Exception_ConcurrencyConflict
     */
    public function manageConcurrentUpdates($applicationId, Tinebase_Record_Interface $newRecord, Tinebase_Record_Interface $curRecord)
    {
        if (! $newRecord->has('seq')) {
            /** @noinspection PhpDeprecationInspection */
            return $this->manageConcurrentUpdatesByTimestamp($newRecord, $curRecord, get_class($newRecord), 'Sql', $newRecord->getId());
        }

        $this->_applicationId = $applicationId;

        if ($curRecord->seq != $newRecord->seq) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                " Concurrent updates: current record last updated '" .
                ($curRecord->last_modified_time instanceof DateTime ? $curRecord->last_modified_time : 'unknown') .
                "' where record to be updated was last updated '" .
                ($newRecord->last_modified_time instanceof DateTime ? $newRecord->last_modified_time : 
                    ($curRecord->creation_time instanceof DateTime ? $curRecord->creation_time : 'unknown')) .
                "' / current sequence: " . $curRecord->seq . " - new record sequence: " . $newRecord->seq);
            
            $loggedMods = $this->getModificationsBySeq($applicationId, $newRecord, $curRecord->seq)->filter('change_type', Tinebase_Timemachine_ModificationLog::UPDATED);
            
            // effective modifications made to the record after current user got his record
            $diff = $this->computeDiff($loggedMods);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                " During the concurrent update, the following changes have been made: " .
                print_r($diff->toArray(),true));
            
            $this->_resolveDiff($diff, $newRecord);

            return $diff;
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " No concurrent updates.");
        }
        
        return null;
    }
    
    /**
     * we loop over the diff! -> changes on fields which have no diff in storage are not in the loop!
     *
     * @param Tinebase_Record_Diff $diff
     * @param Tinebase_Record_Interface $newRecord
     */
    protected function _resolveDiff(Tinebase_Record_Diff $diff, Tinebase_Record_Interface $newRecord)
    {
        if (!is_array($diff->diff)) {
            // nothing to do
            return;
        }

        $diffArray = $diff->diff;
        /** @var Tinebase_Record_Abstract $newRecord */
        $newRecord->_convertISO8601ToDateTime($diffArray);

        foreach ($diffArray as $key => $value) {
            $newUserValue = isset($newRecord->$key) ? Tinebase_Helper::normalizeLineBreaks($newRecord->$key) : NULL;
            $result = $newRecord->resolveConcurrencyUpdate($key, $value, $diff->oldData[$key]);
            if (null !== $result) {
                if (true === $result) {
                    continue;
                }
                $this->_nonResolvableConflict($newUserValue, $key, $diff);
            }

            if (is_array($value) && count($value) === 4 && isset($value['model']) && isset($value['diff'])
                    && isset($value['oldData'])) {
                $value = new Tinebase_Record_Diff($value);
            } elseif (is_array($value) && count($value) === 4 &&
                isset($value['model']) && isset($value['added']) &&
                isset($value['removed']) && isset($value['modified'])) {
                $value = new Tinebase_Record_RecordSetDiff($value);
            }

            if (isset($newRecord->$key) && $newUserValue == Tinebase_Helper::normalizeLineBreaks($value)) {
                //$this->_resolveScalarSameValue($newRecord, $diff);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " User updated to same value for field '" . $key . "', nothing to do.");
            
            } elseif (! isset($newRecord[$key])) {
                if (!is_object($value)) {
                    if ($newRecord->has($key)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' Merge current value into update data, as it was empty/not set in update data.');
                        }

                        $newRecord->{$key} = $value;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                                . ' It seems that the attribute ' . $key . ' no longer exists in this record. Skipping ...');
                        }
                    }
                }
            } elseif ($newUserValue == Tinebase_Helper::normalizeLineBreaks($diff->oldData[$key])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Merge current value into update data, as it was not changed in update request.');
                }
                if ($newRecord->has($key)) {
                    $newRecord->{$key} = $value;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' It seems that the attribute ' . $key . ' no longer exists in this record. Skipping ...');
                    }
                }
            } elseif ($newRecord[$key] instanceof Tinebase_Record_RecordSet && $value instanceof Tinebase_Record_RecordSetDiff) {
                $this->_resolveRecordSetMergeUpdate($newRecord, $key, $value);
            
            } else {
                $this->_nonResolvableConflict($newUserValue, $key, $diff);
            }
        }
    }
    
    /**
     * Update to same value, nothing to do
     * 
     * @param Tinebase_Record_Interface $newRecord
     * @param Tinebase_Record_Diff $diff
     *
    protected function _resolveScalarSameValue(Tinebase_Record_Interface $newRecord, Tinebase_Record_Diff $diff)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " User updated to same value for field '" . $diff->modified_attribute . "', nothing to do.");
    }*/

    /**
     * Merge current value into update data, as it was not changed in update request
     * 
     * @param Tinebase_Record_Interface $newRecord
     * @param Tinebase_Record_Diff $diff
     *
    protected function _resolveScalarMergeUpdate(Tinebase_Record_Interface $newRecord, Tinebase_Record_Diff $diff)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Merge current value into update data, as it was not changed in update request.');
        if ($newRecord->has($diff->modified_attribute)) {
            $newRecord[$diff->modified_attribute] = $diff->new_value;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' It seems that the attribute ' . $diff->modified_attribute . ' no longer exists in this record. Skipping ...');
        }
    } */

    /**
     * record set diff resolving
     *
     * @param Tinebase_Record_Interface $newRecord
     * @param string $attribute
     * @param Tinebase_Record_RecordSetDiff $newValue
     * @throws Tinebase_Exception_ConcurrencyConflict
     */
    protected function _resolveRecordSetMergeUpdate(Tinebase_Record_Interface $newRecord, $attribute, $newValue)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Try to merge record set changes of record attribute " . $attribute);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' New record: ' . print_r($newRecord->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Mod log: ' . print_r($newValue->toArray(true), TRUE));

        $concurrentChangeDiff = $newValue;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' RecordSet diff: ' . print_r($concurrentChangeDiff->toArray(), TRUE));
        
        foreach ($concurrentChangeDiff->added as $added) {
            /** @var Tinebase_Record_Abstract $addedRecord */
            $addedRecord = new $concurrentChangeDiff->model($added);
            if (! $newRecord->$attribute->getById($addedRecord->getId())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Adding recently added record " . $addedRecord->getId());
                $newRecord->$attribute->addRecord($addedRecord);
            }
        }
        
        foreach ($concurrentChangeDiff->removed as $removed) {
            /** @var Tinebase_Record_Abstract $removedRecord */
            $removedRecord = new $concurrentChangeDiff->model($removed);
            /** @var Tinebase_Record_Abstract $recordToRemove */
            $recordToRemove = $newRecord->$attribute->getById($removedRecord->getId());
            if ($recordToRemove) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Removing record " . $recordToRemove->getId());
                $newRecord->$attribute->removeRecord($recordToRemove);
            }
        }
        
        foreach ($concurrentChangeDiff->modified as $modified) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' modified diff: ' . print_r($modified, TRUE));

            /** @var Tinebase_Record_Abstract $modifiedRecord */
            $modifiedRecord = new $concurrentChangeDiff->model(array_merge(array('id' => $modified['id']), $modified['diff']), TRUE);
            /** @var Tinebase_Record_Abstract $newRecordsRecord */
            $newRecordsRecord = $newRecord->$attribute->getById($modifiedRecord->getId());
            if ($newRecordsRecord && ($newRecordsRecord->has('seq') || $newRecordsRecord->has('last_modified_time'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Managing updates for ' . get_class($newRecordsRecord) . ' record ' . $newRecordsRecord->getId());
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' new record: ' . print_r($newRecordsRecord->toArray(), TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' modified record: ' . print_r($modifiedRecord->toArray(), TRUE));

                if (null === $this->_applicationId) {
                    throw new Tinebase_Exception_UnexpectedValue('application_id needs to be set here');
                }
                $this->manageConcurrentUpdates($this->_applicationId, $newRecordsRecord, $modifiedRecord);
            } else {
                throw new Tinebase_Exception_ConcurrencyConflict('concurrency conflict - modified record changes could not be merged!');
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' New record after merge: ' . print_r($newRecord->toArray(), TRUE));
    }
    
    /**
     * Non resolvable concurrency conflict detected
     * 
     * @param string $newUserValue
     * @param string $attribute
     * @param Tinebase_Record_Diff $diff
     * @throws Tinebase_Exception_ConcurrencyConflict
     */
    protected function _nonResolvableConflict($newUserValue, $attribute, Tinebase_Record_Diff $diff)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ 
            . " Non resolvable conflict for field '" . $attribute . "'!");
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' New user value: ' . var_export($newUserValue, TRUE)
            . ' New diff value: ' . var_export($diff->diff[$attribute], TRUE)
            . ' Old diff value: ' . var_export($diff->oldData[$attribute], TRUE));
        
        throw new Tinebase_Exception_ConcurrencyConflict('concurrency conflict!');
    }
    
    /**
     * merges changes made to local storage on concurrent updates into the new record 
     * 
     * @param  Tinebase_Record_Interface $_newRecord record from user data
     * @param  Tinebase_Record_Interface $_curRecord record from storage
     * @param  string $_model
     * @param  string $_backend
     * @param  string $_id
     * @return Tinebase_Record_Diff with resolved concurrent updates
     * @throws Tinebase_Exception_ConcurrencyConflict
     * 
     * @deprecated this should be removed when all records have seq(uence)
     */
    public function manageConcurrentUpdatesByTimestamp(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_curRecord, $_model, $_backend, $_id)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Calling deprecated method. Model ' . $_model . ' should get a seq property.');
        
        list($appName) = explode('_', $_model);
        
        // handle concurrent updates on unmodified records
        if (! $_newRecord->last_modified_time instanceof DateTime) {
            if ($_curRecord->creation_time instanceof DateTime) {
                $_newRecord->last_modified_time = clone $_curRecord->creation_time;
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Something went wrong! No creation_time was set in current record: ' 
                    . print_r($_curRecord->toArray(), TRUE)
                );
                return null;
            }
        }
        
        if ($_curRecord->last_modified_time instanceof DateTime && !$_curRecord->last_modified_time->equals($_newRecord->last_modified_time)) {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " concurrent updates: current record last updated '" .
                $_curRecord->last_modified_time . "' where record to be updated was last updated '" . $_newRecord->last_modified_time . "'");
            
            $loggedMods = $this->getModifications($appName, $_id,
                $_model, $_backend, $_newRecord->last_modified_time, $_curRecord->last_modified_time)->filter('change_type', Tinebase_Timemachine_ModificationLog::UPDATED);
            // effective modifications made to the record after current user got his record
            $diff = $this->computeDiff($loggedMods);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " during the concurrent update, the following changes have been made: " .
                print_r($diff->toArray(),true));

            $this->_resolveDiff($diff, $_newRecord);

            return $diff;
        }
        
        return null;
    }
    
    /**
     * computes changes of records and writes them to the logbook
     * 
     * NOTE: expects last_modified_by and last_modified_time to be set
     * properly in the $_newRecord
     * 
     * @param  Tinebase_Record_Interface $_newRecord record from user data
     * @param  Tinebase_Record_Interface $_curRecord record from storage
     * @param  string $_model
     * @param  string $_backend
     * @param  string $_id
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     */
    public function writeModLog($_newRecord, $_curRecord, $_model, $_backend, $_id)
    {
        $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        if (null !== $_curRecord && null !== $_newRecord) {
            $diff = $_curRecord->diff($_newRecord, array_merge($this->_metaProperties, $_newRecord->getModlogOmitFields()));
            $notNullRecord = $_newRecord;
        } else {
            if (null !== $_newRecord) {
                $notNullRecord = $_newRecord;
                $diffProp = 'diff';
            } else {
                $notNullRecord = $_curRecord;
                $diffProp = 'oldData';
            }
            $diffData = $notNullRecord->toArray();

            foreach (array_merge($this->_metaProperties, $notNullRecord->getModlogOmitFields()) as $omit) {
                if (isset($diffData[$omit])) {
                    unset($diffData[$omit]);
                }
            }

            $diff = new Tinebase_Record_Diff(array($diffProp => $diffData));
        }

        if (! $diff->isEmpty()) {
            $updateMetaData = array('seq' => ($notNullRecord->has('seq')) ? $notNullRecord->seq : 0);
            $last_modified_time = $notNullRecord->last_modified_time;
            if (!empty($last_modified_time)) {
                $updateMetaData['last_modified_time'] = $last_modified_time;
            }
            $last_modified_by   = $notNullRecord->last_modified_by;
            if (!empty($last_modified_by)) {
                $updateMetaData['last_modified_by'] = $last_modified_by;
            }
            $commonModLog = $this->_getCommonModlog($_model, $_backend, $updateMetaData, $_id);
            $commonModLog->new_value = json_encode($diff->toArray());
            if (null === $_newRecord) {
                $commonModLog->change_type = self::DELETED;
            } elseif(null === $_curRecord) {
                $commonModLog->change_type = self::CREATED;
            } else {
                $commonModLog->change_type = self::UPDATED;
            }

            if(true === $notNullRecord->isReplicable()) {
                $commonModLog->instance_id = Tinebase_Core::getTinebaseId();
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Diffs: ' . print_r($diff->diff, TRUE));
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' CurRecord: ' . ($_curRecord!==null?print_r($_curRecord->toArray(), TRUE):'null'));
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' NewRecord: ' . ($_newRecord!==null?print_r($_newRecord->toArray(), TRUE):'null'));
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Common modlog: ' . print_r($commonModLog->toArray(), TRUE));
            }

            $this->setModification($commonModLog);

            $modifications->addRecord($commonModLog);
        }

        return $modifications;
    }
    
    /**
     * creates a common modlog record
     * 
     * @param string $_model
     * @param string $_backend
     * @param array $_updateMetaData
     * @param string $_recordId
     * @return Tinebase_Model_ModificationLog
     */
    protected function _getCommonModlog($_model, $_backend, $_updateMetaData = array(), $_recordId = NULL)
    {
        if (empty($_updateMetaData) || ! isset($_updateMetaData['last_modified_by']) ||  ! isset($_updateMetaData['last_modified_time'])) {
            list($currentAccountId, $currentTime) = Tinebase_Timemachine_ModificationLog::getCurrentAccountIdAndTime();
        } else {
            $currentAccountId = $_updateMetaData['last_modified_by'];
            $currentTime      = $_updateMetaData['last_modified_time'];
        }

        $client = Tinebase_Core::get('serverclassname');
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $client .= ' - ' . $_SERVER['HTTP_USER_AGENT'];
        } else {
            $client .= ' - no http user agent present';
        }
        
        list($appName/*, $i, $modelName*/) = explode('_', $_model);
        $commonModLogEntry = new Tinebase_Model_ModificationLog(array(
            'application_id'       => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId(),
            'record_id'            => $_recordId,
            'record_type'          => $_model,
            'record_backend'       => $_backend,
            'modification_time'    => $currentTime,
            'modification_account' => $currentAccountId ? $currentAccountId : 'unknown',
            'seq'                  => (isset($_updateMetaData['seq'])) ? $_updateMetaData['seq'] : 0,
            'client'               => $client
        ), TRUE);
        
        return $commonModLogEntry;
    }

    /**
     * write modlog for multiple records
     *
     * @param array $_ids
     * @param $_currentData
     * @param array $_newData
     * @param string $_model
     * @param string $_backend
     * @param array $updateMetaData
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     * @throws Tinebase_Exception_NotImplemented
     * @internal param array $_oldData
     *
     * TODO instance id is never set in this code path! => thus replication doesn't work here!
     */
    public function writeModLogMultiple($_ids, $_currentData, $_newData, $_model, $_backend, $updateMetaData = array())
    {
        //return new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');

        //throw new Tinebase_Exception_NotImplemented('fix it');

        $commonModLog = $this->_getCommonModlog($_model, $_backend, $updateMetaData);
        
        $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Writing modlog for ' . count($_ids) . ' records.');
        
        foreach ($_ids as $id) {
            $modification = clone $commonModLog;

            $modification->record_id = $id;
            if (isset($updateMetaData['recordSeqs']) && (isset($updateMetaData['recordSeqs'][$id]) || array_key_exists($id, $updateMetaData['recordSeqs']))) {
                $modification->seq = (! empty($updateMetaData['recordSeqs'][$id])) ? $updateMetaData['recordSeqs'][$id] + 1 : 1;
            }
            $diff = new Tinebase_Record_Diff();
            $diff->diff = $_newData;
            $diff->oldData = $_currentData;
            $modification->new_value = json_encode($diff->toArray());

            $this->setModification($modification);
            $modifications->addRecord($modification);
            //$this->_loopModifications($_newData, $commonModLog, $modifications, $_currentData);
        }
        
        return $modifications;
    }
    
    /**
     * undo modlog records defined by filter
     * 
     * @param Tinebase_Model_ModificationLogFilter $filter
     * @param boolean $overwrite should changes made after the detected change be overwritten?
     * @param boolean $dryrun
     * @param string  $attribute limit undo to this attribute
     * @return array
     * 
     * @todo use iterator?
     * @todo return updated records/exceptions?
     * @todo create result model / should be used in Tinebase_Controller_Record_Abstract::updateMultiple, too
     * @todo use transaction with rollback for dryrun?
     * @todo allow to undo tags/customfields/...
     * @todo add interactive mode
     */
    public function undo(Tinebase_Model_ModificationLogFilter $filter, $overwrite = FALSE, $dryrun = FALSE, $attribute = null)
    {
        /* TODO fix this !*/
        $notUndoableFields = array('tags', 'customfields', 'relations');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Filter: ' . print_r($filter->toArray(), TRUE). ' attribute: ' . $attribute);
        
        $modlogRecords = $this->_backend->search($filter, new Tinebase_Model_Pagination(array(
            'sort' => 'instance_seq',
            'dir'  => 'DESC'
        )));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Found ' . count($modlogRecords) . ' modlog records matching the filter.');
        
        $updateCount = 0;
        $failCount = 0;
        $undoneModlogs = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        $currentRecordType = NULL;
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = NULL;
        $controllerCache = array();

        /** @var Tinebase_Model_ModificationLog $modlog */
        foreach ($modlogRecords as $modlog) {
            if ($currentRecordType !== $modlog->record_type || ! isset($controller)) {
                $currentRecordType = $modlog->record_type;
                if (!isset($controllerCache[$modlog->record_type])) {
                    $controller = Tinebase_Core::getApplicationInstance($modlog->record_type);
                    $controllerCache[$modlog->record_type] = $controller;
                } else {
                    $controller = $controllerCache[$modlog->record_type];
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                ' Modlog: ' . print_r($modlog->toArray(), TRUE));


            /* TODO $overwrite check in new code path! */

            $modifiedAttribute = $modlog->modified_attribute;

            try {

                if (empty($modifiedAttribute)) {
                    // new handling using diff!

                    $updateCount++;

                    if (method_exists($controller, 'undoReplicationModificationLog')) {
                        $controller->undoReplicationModificationLog($modlog, $dryrun);
                    } else {

                        if (Tinebase_Timemachine_ModificationLog::CREATED === $modlog->change_type) {
                            if (!$dryrun) {
                                $controller->delete($modlog->record_id);
                            }
                        } elseif (Tinebase_Timemachine_ModificationLog::DELETED === $modlog->change_type) {
                            $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value, true));
                            $model = $modlog->record_type;
                            $record = new $model($diff->oldData, true);
                            if (!$dryrun) {
                                $controller->unDelete($record);
                            }
                        } else {
                            $record = $controller->get($modlog->record_id, null, true, true);
                            $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value, true));
                            $record->undo($diff);

                            if (!$dryrun) {
                                $controller->update($record);
                            }
                        }
                    }

                    // this is the legacy code for old data in existing installations
                } else {

                    $record = $controller->get($modlog->record_id);

                    if (!in_array($modlog->modified_attribute, $notUndoableFields) && ($overwrite || $record->seq === $modlog->seq)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Reverting change id ' . $modlog->getId());

                        $record->{$modlog->modified_attribute} = $modlog->old_value;
                        if (!$dryrun) {
                            $controller->update($record);
                        }
                        $updateCount++;
                        $undoneModlogs->addRecord($modlog);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            ' Not reverting change of ' . $modlog->modified_attribute . ' of record ' . $modlog->record_id);
                    }
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e);
                $failCount++;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Reverted ' . $updateCount . ' modlog changes.');
        
        return array(
            'totalcount'     => $updateCount,
            'failcount'      => $failCount,
            'undoneModlogs'  => $undoneModlogs,
//             'exceptions' => NULL,
//             'results'    => NULL,
        );
    }
    
    /**
     * sets record modification data and protects it from spoofing
     * 
     * @param   Tinebase_Record_Interface $_newRecord record from user data
     * @param   string                    $_action    one of {create|update|delete}
     * @param   Tinebase_Record_Interface $_curRecord record from storage
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function setRecordMetaData(Tinebase_Record_Interface $_newRecord, $_action, Tinebase_Record_Interface $_curRecord = NULL)
    {
        // disable validation as this is slow and we are setting valid data here
        $bypassFilters = $_newRecord->bypassFilters;
        $_newRecord->bypassFilters = TRUE;
        
        list($currentAccountId, $currentTime) = self::getCurrentAccountIdAndTime();
        
        // spoofing protection
        $_newRecord->created_by         = $_curRecord ? $_curRecord->created_by : NULL;
        $_newRecord->creation_time      = $_curRecord ? $_curRecord->creation_time : NULL;
        $_newRecord->last_modified_by   = $_curRecord ? $_curRecord->last_modified_by : NULL;
        $_newRecord->last_modified_time = $_curRecord ? $_curRecord->last_modified_time : NULL;
        
        if ($_newRecord->has('is_deleted')) {
            $_newRecord->is_deleted     = $_curRecord ? $_curRecord->is_deleted : 0;
            $_newRecord->deleted_time   = $_curRecord ? $_curRecord->deleted_time : NULL;
            $_newRecord->deleted_by     = $_curRecord ? $_curRecord->deleted_by : NULL;
        }
        
        switch ($_action) {
            case 'create':
                $_newRecord->created_by    = $currentAccountId;
                $_newRecord->creation_time = $currentTime;
                if ($_newRecord->has('seq')) {
                    $_newRecord->seq       = 1;
                }
                break;
            case 'update':
                $_newRecord->last_modified_by   = $currentAccountId;
                $_newRecord->last_modified_time = $currentTime;
                self::increaseRecordSequence($_newRecord, $_curRecord);
                break;
            case 'delete':
                $_newRecord->deleted_by   = $currentAccountId;
                $_newRecord->deleted_time = $currentTime;
                $_newRecord->is_deleted   = true;
                self::increaseRecordSequence($_newRecord, $_curRecord);
                break;
            case 'undelete':
                $_newRecord->deleted_by   = null;
                $_newRecord->deleted_time = null;
                $_newRecord->is_deleted   = 0;
                self::increaseRecordSequence($_newRecord, $_curRecord);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('Action must be one of {create|update|delete|undelete}.');
                break;
        }
        
        $_newRecord->bypassFilters = $bypassFilters;
    }
    
    /**
     * increase record sequence
     * 
     * @param Tinebase_Record_Interface $newRecord
     * @param Tinebase_Record_Interface $curRecord
     */
    public static function increaseRecordSequence(Tinebase_Record_Interface $newRecord, Tinebase_Record_Interface $curRecord = NULL)
    {
        if (is_object($curRecord) && $curRecord->has('seq')) {
            $newRecord->seq = (int) $curRecord->seq +1;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Increasing seq of ' . get_class($newRecord) . ' with id ' . $newRecord->getId() .
                ' from ' . ($newRecord->seq - 1) . ' to ' . $newRecord->seq);
        }
    }
    
    /**
     * returns current account id and time
     * 
     * @return array
     */
    public static function getCurrentAccountIdAndTime()
    {
        $currentAccount   = Tinebase_Core::getUser();
        $currentAccountId = $currentAccount instanceof Tinebase_Record_Interface ? $currentAccount->getId(): NULL;
        $currentTime      = new Tinebase_DateTime();

        return array($currentAccountId, $currentTime);
    }

    /**
     * removes modlog entries for that application
     *
     * @param Tinebase_Model_Application $_application
     *
     * @return void
     */
    public function removeApplication(Tinebase_Model_Application $_application)
    {
        $this->_backend->deleteByProperty($_application->getId(), 'application_id');
    }

    public static function getModifiedAttributes(Tinebase_Record_RecordSet $modLogs)
    {
        $result = array();

        /** @var Tinebase_Model_ModificationLog $modlog */
        foreach ($modLogs as $modlog) {
            $modAtrb = $modlog->modified_attribute;
            if (empty($modAtrb)) {
                $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value, true));
                $result = array_merge($result, $diff->diff);
            } else {
                $result[$modAtrb] = null;
            }
        }

        return array_keys($result);
    }

    public function fetchBlobFromMaster($hash)
    {
        $slaveConfiguration = Tinebase_Config::getInstance()->{Tinebase_Config::REPLICATION_SLAVE};
        $tine20Url = $slaveConfiguration->{Tinebase_Config::MASTER_URL};
        $tine20LoginName = $slaveConfiguration->{Tinebase_Config::MASTER_USERNAME};
        $tine20Password = $slaveConfiguration->{Tinebase_Config::MASTER_PASSWORD};

        // check if we are a replication slave
        if (empty($tine20Url) || empty($tine20LoginName) || empty($tine20Password)) {
            return true;
        }

        $tine20Service = new Zend_Service_Tine20($tine20Url, new Zend_Http_Client(null, [
            'timeout' => 25
        ]));

        $authResponse = $tine20Service->login($tine20LoginName, $tine20Password);
        if (!is_array($authResponse) || !isset($authResponse['success']) || $authResponse['success'] !== true) {
            throw new Tinebase_Exception_AccessDenied('login failed');
        }
        unset($authResponse);

        $tinebaseProxy = $tine20Service->getProxy('Tinebase');
        /** @noinspection PhpUndefinedMethodInspection */
        $response = $tinebaseProxy->getBlob($hash);
        if (!is_array($response) || !isset($response['success']) || true !== $response['success'] ||
                !isset($response['data'])) {
            throw new Tinebase_Exception_Backend('could not fetch blob from master successfully: ' . $hash);
        }
        if (false === ($data = base64_decode($response['data'], true))) {
            throw new Tinebase_Exception_Backend('fetched blob from master was not proper base64: ' . $hash);
        }

        $fileObject = new Tinebase_Model_Tree_FileObject(array('hash' => $hash), true);
        $path = $fileObject->getFilesystemPath();
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path));
        }
        if (false === file_put_contents($path, $data)) {
            throw new Tinebase_Exception_Backend('fetched blob from master could not written to disk: ' . $hash);
        }
    }

    /**
     * @return bool
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Backend
     */
    public function readModificationLogFromMaster()
    {
        $slaveConfiguration = Tinebase_Config::getInstance()->{Tinebase_Config::REPLICATION_SLAVE};
        $tine20Url = $slaveConfiguration->{Tinebase_Config::MASTER_URL};
        $tine20LoginName = $slaveConfiguration->{Tinebase_Config::MASTER_USERNAME};
        $tine20Password = $slaveConfiguration->{Tinebase_Config::MASTER_PASSWORD};

        // check if we are a replication slave
        if (empty($tine20Url) || empty($tine20LoginName) || empty($tine20Password)) {
            return true;
        }

        $this->_readModificationLogFromMasterLockId = __METHOD__;
        $result = Tinebase_Core::acquireMultiServerLock(__METHOD__);
        if (false === $result) {
            // we are already running
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                ' failed to aquire multi server lock, it seems we are already running in a parallel process.');
            return true;
        }

        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' trying to connect to master host: ' . $tine20Url . ' with user: ' . $tine20LoginName);
            }

            $tine20Service = new Zend_Service_Tine20($tine20Url);

            $authResponse = null;
            try {
                $authResponse = $tine20Service->login($tine20LoginName, $tine20Password);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
            if (!is_array($authResponse) || !isset($authResponse['success']) || $authResponse['success'] !== true) {
                throw new Tinebase_Exception_AccessDenied('login failed');
            }
            unset($authResponse);

            //get replication state:
            $masterReplicationId = intval(Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                Tinebase_Application::STATE_REPLICATION_MASTER_ID));

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' master replication id: ' . $masterReplicationId);
            }

            $tinebaseProxy = $tine20Service->getProxy('Tinebase');

            try {
                /** @noinspection PhpUndefinedMethodInspection */
                $result = $tinebaseProxy->getReplicationModificationLogs($masterReplicationId, 100);
                /* TODO make the amount above configurable  */
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                throw new Tinebase_Exception_Backend('could not getReplicationModificationLogs from master');
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' received ' . count($result['results']) . ' modification logs');
            }

            // memory cleanup
            unset($tinebaseProxy);
            unset($tine20Service);

            $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', $result['results']);
            unset($result);

            return $this->applyReplicationModLogs($modifications);
        } finally {
            Tinebase_Core::releaseMultiServerLock(__METHOD__);
        }
    }

    /**
     * apply modification logs from a replication master locally
     *
     * @param Tinebase_Record_RecordSet $modifications
     * @return boolean
     */
    public function applyReplicationModLogs(Tinebase_Record_RecordSet $modifications)
    {
        $currentRecordType = NULL;
        $controller = NULL;
        $controllerCache = array();

        $transactionManager = Tinebase_TransactionManager::getInstance();
        $db = Tinebase_Core::getDb();
        $applicationController = Tinebase_Application::getInstance();
        /** @var Tinebase_Model_Application $tinebaseApplication */
        $tinebaseApplication = $applicationController->getApplicationByName('Tinebase');
        $lock = null;
        if (null !== $this->_readModificationLogFromMasterLockId) {
            $lock = Tinebase_Core::getMultiServerLock($this->_readModificationLogFromMasterLockId);
        }

        /** @var Tinebase_Model_ModificationLog $modification */
        foreach ($modifications as $modification)
        {
            $transactionId = $transactionManager->startTransaction($db);

            $this->_externalInstanceId = $modification->instance_id;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Processing master sequence ' . $modification->instance_seq);

            try {
                if ($currentRecordType !== $modification->record_type || !isset($controller)) {
                    $currentRecordType = $modification->record_type;
                    if (!isset($controllerCache[$modification->record_type])) {
                        $controller = Tinebase_Core::getApplicationInstance($modification->record_type, '', true);
                        $controllerCache[$modification->record_type] = $controller;
                    } else {
                        $controller = $controllerCache[$modification->record_type];
                    }
                }

                if (method_exists($controller, 'applyReplicationModificationLog')) {
                    $controller->applyReplicationModificationLog($modification);
                } else {
                    static::defaultApply($modification, $controller);
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Update replication master id to ' . $modification->instance_seq);
                $applicationController->setApplicationState($tinebaseApplication,
                    Tinebase_Application::STATE_REPLICATION_MASTER_ID, $modification->instance_seq);

                if (null !== $lock && !$lock->isLocked()) {
                    throw new Tinebase_Exception_Backend('lock of type ' . get_class($lock) . ' lost lock');
                }

                $transactionManager->commitTransaction($transactionId);

            } catch (Exception $e) {
                $this->_externalInstanceId = null;

                Tinebase_Exception::log($e, false);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Could not apply modification: ' . print_r($modification->toArray(), true));

                $transactionManager->rollBack();

                // notify configured email addresses about replication failure
                $config = Tinebase_Config::getInstance()->get(Tinebase_Config::REPLICATION_SLAVE);
                if (is_array($config->{Tinebase_Config::ERROR_NOTIFICATION_LIST}) &&
                        count($config->{Tinebase_Config::ERROR_NOTIFICATION_LIST}) > 0) {

                    $plain = "Error applying modlog: \n" . print_r($modification->toArray(), true);
                    $plain .= "\n\n" . $e->getMessage() . PHP_EOL . PHP_EOL . $e->getTraceAsString();

                    foreach ($config->{Tinebase_Config::ERROR_NOTIFICATION_LIST} as $recipient) {
                        $recipients = array(new Addressbook_Model_Contact(array('email' => $recipient), true));
                        try {
                            Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), $recipients,
                                'replication client error', $plain);
                        } catch (Exception $e) {
                            // skipping recipient
                            Tinebase_Exception::log($e);
                        }
                    }
                }

                // must not happen, continuing pointless!
                return false;
            }

            Tinebase_Lock::keepLocksAlive();
        }

        $this->_externalInstanceId = null;

        return true;
    }

    /**
     * @param Tinebase_Model_ModificationLog $_modification
     * @param Tinebase_Controller_Record_Abstract $_controller
     * @throws Tinebase_Exception
     */
    public static function defaultApply(Tinebase_Model_ModificationLog $_modification, $_controller)
    {
        switch ($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $model = $_modification->record_type;
                $record = new $model($diff->diff);
                $_controller->create($record);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $record = $_controller->get($_modification->record_id, NULL, true, true);
                $record->applyDiff($diff);
                $_controller->update($record);
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                $_controller->delete($_modification->record_id);
                break;

            default:
                throw new Tinebase_Exception('unknown Tinebase_Model_ModificationLog->change_type: ' . $_modification->change_type);
        }
    }

    /**
     * @param int $count
     */
    public function increaseReplicationMasterId($count = 1)
    {
        $applicationController = Tinebase_Application::getInstance();
        $tinebase = $applicationController->getApplicationByName('Tinebase');

        $applicationController->setApplicationState($tinebase,
            Tinebase_Application::STATE_REPLICATION_MASTER_ID, intval($applicationController->getApplicationState(
                $tinebase, Tinebase_Application::STATE_REPLICATION_MASTER_ID)) + intval($count));
    }
}
