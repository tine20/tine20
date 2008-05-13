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
     * holdes the instance of the singleton
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
        $this->_table->setRowClass('Tinebase_Timemachine_Model_ModificationLog');
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
     * @return Tinebase_Record_RecordSet RecordSet of Tinebase_Timemachine_Model_ModificationLog
     */
    public function getModifications( $_application,  $_id, $_type = NULL, $_backend, Zend_Date $_from, Zend_Date $_until,  $_modifierId = NULL ) {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_application);
        
        $isoDef = 'YYYY-MM-ddTHH:mm:ss';
        
        $db = $this->_table->getAdapter();
        $select = $db->select()
            ->from($this->_tablename)
            ->order('modification_time ASC')
            ->where('application_id = ' . $application->id)
            ->where($db->quoteInto('record_id = ?', $_id))
            ->where($db->quoteInto('modification_time > ?', $_from->toString($isoDef)))
            ->where($db->quoteInto('modification_time <= ?', $_until->toString($isoDef)));
            
       if ($_type) {
           $select->where($db->quoteInto('record_type LIKE ?', $_type));
       }
       if ($_backend) {
           $select->where($db->quoteInto('record_backend LIKE ?', $_backend));
       }
       if ($_modifierId) {
           $select->where($db->quoteInto('modification_account = ?', $_modifierId));
       }
       
       $stmt = $db->query($select);
       $resultArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
       
       $modifications = new Tinebase_Record_RecordSet('Tinebase_Timemachine_Model_ModificationLog', $resultArray);
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
        return new Tinebase_Record_RecordSet('Tinebase_Timemachine_Model_ModificationLog', $diff);
    }
    
    /**
     * Returns a single logbook entry identified by an logbook identifier
     * 
     * @param string _id
     * @return Tinebase_Timemachine_Model_ModificationLog
     */
    public function getModification( $_id ) {
    	$db = $this->_table->getAdapter();
    	$stmt = $db->query($db->select()
    	   ->from($this->_tablename)
    	   ->where($this->_table->getAdapter()->quoteInto('id = ?', $_id))
    	);
        $RawLogEntry = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (empty($RawLogEntry)) {
            throw new Exception("Modification Log with id: $_id not found!");
        }
        return new Tinebase_Timemachine_Model_ModificationLog($RawLogEntry[0], true); 
        
    } // end of member function getModification
    
    /**
     * Saves a logbook record
     * 
     * @param Tinebase_Timemachine_Model_ModificationLog _modification 
     * @return string id;
     */
    public function setModification( Tinebase_Timemachine_Model_ModificationLog $_modification ) {
        if ($_modification->isValid()) {
        	$id = $_modification->generateUID();
            $_modification->setId($id);
            $_modification->convertDates = true;
            $modificationArray = $_modification->toArray();
            
            $this->_table->insert($modificationArray);
        } else {
            throw new Exception(
                "_modification data is not valid! \n" . 
                print_r($_modification->getValidationErrors(), true)
            );
        }
        return $id;
    } // end of member function setModification
    
} // end of Tinebase_Timemachine_ModificationLog
?>
