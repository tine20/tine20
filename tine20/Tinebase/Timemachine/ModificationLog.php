<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
 * @todo Add registry for logbook starttime and methods to throw away logbook 
 * entries. Throw exceptions when times are requested which are not in the 
 * log anymore!
 * 
 * @package Tinebase
 * @subpackage Timemachine
 */
class Tinebase_Timemachine_ModificationLog
{
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
     */
    protected $_metaProperties = array(
        'created_by',
        'creation_time',
        'seq',
        'last_modified_by',
        'last_modified_time',
        'is_deleted',
        'deleted_time',
        'deleted_by'
    );
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Timemachine_ModificationLog
     */
    private static $instance = NULL;
    
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
    }
    
    /**
     * Returns modification of a given record in a given timespan
     * 
     * @param string _application application of given identifier  
     * @param string _id identifier to retreave modification log for
     * @param string _type 
     * @param string _backend 
     * @param Zend_Date _from beginning point of timespan, excluding point itself
     * @param Zend_Date _until end point of timespan, including point itself 
     * @param int _modifierId optional
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     */
    public function getModifications( $_application,  $_id, $_type = NULL, $_backend, Zend_Date $_from, Zend_Date $_until,  $_modifierId = NULL ) {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        $isoDef = 'yyyy-MM-ddTHH:mm:ss';
        
        $db = $this->_table->getAdapter();
        $select = $db->select()
            ->from($this->_tablename)
            ->order('modification_time ASC')
            ->where($db->quoteInto($db->quoteIdentifier('application_id') . ' = ?', $application->id))
            ->where($db->quoteInto($db->quoteIdentifier('record_id') . ' = ?', $_id))
            ->where($db->quoteInto($db->quoteIdentifier('modification_time') . ' > ?', $_from->toString($isoDef)))
            ->where($db->quoteInto($db->quoteIdentifier('modification_time') . ' <= ?', $_until->toString($isoDef)));
            
       if ($_type) {
           $select->where($db->quoteInto($db->quoteIdentifier('record_type') . ' LIKE ?', $_type));
       }
       if ($_backend) {
           $select->where($db->quoteInto($db->quoteIdentifier('record_backend') . ' LIKE ?', $_backend));
       }
       if ($_modifierId) {
           $select->where($db->quoteInto($db->quoteIdentifier('modification_account') . ' = ?', $_modifierId));
       }
       
       $stmt = $db->query($select);
       $resultArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
       
       $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', $resultArray);
       return $modifications;
    } // end of member function getModifications

    /**
     * Computes effective difference from a set of modifications
     * 
     * If a attribute got changed more than once, the returned diff has all
     * properties of the last change to the attribute, besides the 
     * 'modified_from', which holds the modified_from of the first change.
     * 
     * @param Tinebase_Record_RecordSet _modifications
     * @return Tinebase_Record_RecordSet differences
     */
    public function computeDiff(Tinebase_Record_RecordSet $_modifications) {
        $diff = array();
        foreach ($_modifications as $modification) {
            if (array_key_exists($modification->modified_attribute, $diff)) {
                $modification->old_value = $diff[$modification->modified_attribute]->old_value;
            }
            $diff[$modification->modified_attribute] = $modification;
        }
        return new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', $diff);
    }
    
    /**
     * Returns a single logbook entry identified by an logbook identifier
     * 
     * @param   string _id
     * @return  Tinebase_Model_ModificationLog
     * @throws  Tinebase_Exception_NotFound
     */
    public function getModification( $_id ) {
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
        
    } // end of member function getModification
    
    /**
     * Saves a logbook record
     * 
     * @param   Tinebase_Model_ModificationLog _modification 
     * @return  string id;
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function setModification(Tinebase_Model_ModificationLog $_modification) {
        if ($_modification->isValid()) {
        	$id = $_modification->generateUID();
            $_modification->setId($id);
            $_modification->convertDates = true;
            $modificationArray = $_modification->toArray();
            
            $this->_table->insert($modificationArray);
        } else {
            throw new Tinebase_Exception_Record_Validation(
                "_modification data is not valid! \n" . 
                print_r($_modification->getValidationErrors(), true)
            );
        }
        return $id;
    } // end of member function setModification
    
    /**
     * merges changes made to local storage on concurrent updates into the new record 
     * 
     * @param  Tinebase_Record_Interface $_newRecord record from user data
     * @param  Tinebase_Record_Interface $_curRecord record from storage
     * @return Tinebase_Record_RecordSet with resolved concurrent updates (Tinebase_Model_ModificationLog records)
     */
    public function manageConcurrentUpdates(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_curRecord, $_model, $_backend, $_id)
    {
        list($appName, $i, $modelName) = explode('_', $_model);
        
        $resolved = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        
        // handle concurrent updates on unmodified records
        if (! $_newRecord->last_modified_time instanceof Zend_Date) {
            
            if ($_curRecord->creation_time instanceof Zend_Date) {
                $_newRecord->last_modified_time = clone $_curRecord->creation_time;    
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                    . ' Something went wrong! No creation_time was set in current record: ' 
                    . print_r($_curRecord->toArray(), TRUE)
                );
                return $resolved;
            }
        }
        
        if($_curRecord->last_modified_time instanceof Zend_Date && !$_curRecord->last_modified_time->equals($_newRecord->last_modified_time)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  concurrent updates: current record last updated '" .
                $_curRecord->last_modified_time . "' where record to be updated was last updated '" . $_newRecord->last_modified_time . "'");
            
            $loggedMods = $this->getModifications($appName, $_id,
                    $_model, $_backend, $_newRecord->last_modified_time, $_curRecord->last_modified_time);
            // effective modifications made to the record after current user got his record
            $diffs = $this->computeDiff($loggedMods);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " during the concurrent update, the following changes have been made: " .
                print_r($diffs->toArray(),true));
            
            // we loop over the diffs! -> changes over fields which have no diff in storage are not in the loop!
            foreach ($diffs as $diff) {
                if ($_newRecord[$diff->modified_attribute] instanceof Zend_Date) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " we can't deal with dates yet -> non resolvable conflict!");
                    throw new Tinebase_Timemachine_Exception_ConcurrencyConflict('concurrency conflict!');
                }
                if ($_newRecord[$diff->modified_attribute] == $diff->new_value) { 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " user updated to same value for field '" .
                    $diff->modified_attribute . "', nothing to do.");
                    $resolved->addRecord($diff);
                } elseif ($_newRecord[$diff->modified_attribute]  == $diff->old_value) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " merge current value into update data, as it was not changed in update request.");
                    $_newRecord[$diff->modified_attribute] = $diff->new_value;
                    $resolved->addRecord($diff);
                } else {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . " non resolvable conflict!");
                    throw new Tinebase_Timemachine_Exception_ConcurrencyConflict('concurrency confilict!');
                }
            }
        }
        
        return $resolved;
        
    } // end of member function manageConcurrentUpdates
    
    /**
     * computes changes of records and writes them to the logbook
     * 
     * NOTE: expects last_modified_by and last_modified_time to be set
     * properly in the $_newRecord
     * 
     * @param  Tinebase_Record_Abstract $_newRecord record from user data
     * @param  Tinebase_Record_Abstract $_curRecord record from storage
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Model_ModificationLog
     * 
     * @todo move more 'toOmmit' fields to record
     */
    public function writeModLog($_newRecord, $_curRecord, $_model, $_backend, $_id)
    {
        list($appName, $i, $modelName) = explode('_', $_model);
        
        $modLogEntry = new Tinebase_Model_ModificationLog(array(
            'application_id'       => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId(),
            'record_id'            => $_id,
            'record_type'          => $_model,
            'record_backend'       => $_backend,
            'modification_time'    => $_newRecord->last_modified_time,
            'modification_account' => $_newRecord->last_modified_by
        ),true);
            
        $diffs = $_curRecord->diff($_newRecord);
        
        $modifications = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        
        // ommit second order records and jpegphoto for the moment
        $toOmmit = array_merge($this->_metaProperties, array(
            'tags',
            'relations',
            'notes',
            'products',
            'jpegphoto',
            'grants',
            'customfields',
            'exdate',
            'attendee',
            'alarms'
        ));
        $toOmmit = array_merge($toOmmit, $_curRecord->getModlogOmmitFields());
        
        foreach ($diffs as $field => $newValue) {
            if(! in_array($field, $toOmmit)) {
                $curValue = $_curRecord->$field;
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " field '$field' changed from '$curValue' to '$newValue'");
                
                $modLogEntry->modified_attribute = $field;
                $modLogEntry->old_value = $curValue;
                $modLogEntry->new_value = $newValue;
                $modLogEntry->setId($this->setModification($modLogEntry));
                $modifications->addRecord(clone $modLogEntry);
            }
        }
        return $modifications;
    }
    
    /**
     * sets record modification data and protects it from spoofing
     * 
     * @param   Tinebase_Record_Abstract $_newRecord record from user data
     * @param   string                    $_action    one of {create|update|delete}
     * @param   Tinebase_Record_Abstract $_curRecord record from storage
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function setRecordMetaData($_newRecord, $_action, $_curRecord=NULL)
    {
        $currentAccount   = Tinebase_Core::getUser();
        $currentAccountId = $currentAccount instanceof Tinebase_Record_Abstract ? $currentAccount->getId(): NULL;
        $currentTime      = Zend_Date::now();
        
        // spoofing protection
        $_newRecord->created_by         = $_curRecord ? $_curRecord->created_by : NULL;
        $_newRecord->creation_time      = $_curRecord ? $_curRecord->creation_time : NULL;
        $_newRecord->last_modified_by   = $_curRecord ? $_curRecord->last_modified_by : NULL;
        $_newRecord->last_modified_time = $_curRecord ? $_curRecord->last_modified_time : NULL;
        $_newRecord->is_deleted         = $_curRecord ? $_curRecord->is_deleted : 0;
        $_newRecord->deleted_time       = $_curRecord ? $_curRecord->deleted_time : NULL;
        $_newRecord->deleted_by         = $_curRecord ? $_curRecord->deleted_by : NULL;
        
        switch ($_action) {
            case 'create':
                $_newRecord->created_by    = $currentAccountId;
                $_newRecord->creation_time = $currentTime;
                break;
            case 'update':
                $_newRecord->last_modified_by   = $currentAccountId;
                $_newRecord->last_modified_time = $currentTime;
                if ($_curRecord->has('seq')) {
                	$_newRecord->seq = (int) $_curRecord->seq +1;
                }
                break;
            case 'delete':
                $_newRecord->deleted_by   = $currentAccountId;
                $_newRecord->deleted_time = $currentTime;
                $_newRecord->is_deleted   = true;
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('Action must be one of {create|update|delete}.');
                break;
        }
    } // end of static function setRecordMetaData
    
} // end of Tinebase_Timemachine_ModificationLog
?>
