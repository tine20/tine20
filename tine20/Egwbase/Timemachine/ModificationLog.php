<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * ModificationLog is used by Egwbase_Timemachine_Abstract. If an application
 * backened extends Egwbase_Timemachine_Abstract, it MUST use 
 * Egwbase_Timemachine_ModificationLog to track modifications
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
 * @package Egwbase
 * @subpackage Timemachine
 */
class Egwbase_Timemachine_ModificationLog
{
    /**
     * Tablename SQL_TABLE_PREFIX . timemachine_modificationlog
     *
     * @var string
     */
    protected $_tablename = 'timemachine_modificationlog';
    
    /**
     * Holds table instance for timemachine_history table
     *
     * @var Egwbase_Db_Table
     */
    protected $_table = NULL;
    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Timemachine_ModificationLog
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Timemachine_ModificationLog
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Timemachine_ModificationLog();
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
        
        // temporaray setup
        try {
            $this->_table = new Egwbase_Db_Table(array('name' => $this->_tablename));
            $this->_table->setRowClass('Egwbase_Timemachine_Model_ModificationLog');
            
        } catch (Exception $e) {
            $this->setupTable();
            $this->_table = new Egwbase_Db_Table(array('name' => $this->_tablename));
            $this->_table->setRowClass('Egwbase_Timemachine_Model_ModificationLog');
        }
    }
    
    /**
     * Returns modification of a given record in a given timespan
     * 
     * @param string _application application of given identifier  
     * @param int _identifier identifier to retreave modification log for
     * @param string _type 
     * @param string _backend 
     * @param Zend_Date _from beginning point of timespan, excluding point itself
     * @param Zend_Date _until end point of timespan, including point itself 
     * @param int _modifierId optional
     * @return Egwbase_Record_RecordSet RecordSet of Egwbase_Timemachine_Model_ModificationLog
     */
    public function getModifications( $_application,  $_identifier, $_type = NULL, $_backend, Zend_Date $_from, Zend_Date $_until,  $_modifierId = NULL ) {
        $application = Egwbase_Application::getInstance()->getApplicationByName($_application);
        
        $isoDef = 'YYYY-MM-ddTHH:mm:ss';
        
        $db = $this->_table->getAdapter();
        $select = $db->select()
            ->from($this->_tablename)
            ->order('modification_time ASC')
            ->where('application = ' . $application->app_id)
            ->where($db->quoteInto('record_identifier = ?', $_identifier))
            ->where($db->quoteInto('modification_time > ?', $_from->toString($isoDef)))
            ->where($db->quoteInto('modification_time <= ?', $_until->toString($isoDef)));
            
       if (is_int($_modifierId)) {
           $select->where($db->quoteInto('modification_account = ?', $_modifierId));
       }
       
       $stmt = $db->query($select);
       $resultArray = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
       
       $modifications = new Egwbase_Record_RecordSet($resultArray, 'Egwbase_Timemachine_Model_ModificationLog');
       return $modifications;
    } // end of member function getModifications

    /**
     * Computes effective difference from a set of modifications
     * 
     * If a attribute got changed more than once, the returned diff has all
     * properties of the last change to the attribute, besides the 
     * 'modified_from', which holds the modified_from of the first change.
     * 
     * @param Egwbase_Record_RecordSet _modifications
     * @return Egwbase_Record_RecordSet differences
     */
    public function computeDiff(Egwbase_Record_RecordSet $_modifications) {
        $diff = array();
        foreach ($_modifications as $modification) {
            if (array_key_exists($modification->modified_attribute, $diff)) {
                $modification->modified_from = $diff[$modification->modified_attribute]->modified_from;
            }
            $diff[$modification->modified_attribute] = $modification;
        }
        return new Egwbase_Record_RecordSet($diff, 'Egwbase_Timemachine_Model_ModificationLog');
    }
    
    /**
     * Returns a single logbook entry identified by an logbook identifier
     * 
     * @param int _identifier 
     * @return Egwbase_Timemachine_Model_ModificationLog
     */
    public function getModification( $_identifier ) {
        
        $LogEntry = $this->_table->find($_identifier)->current();
        return $LogEntry;
    } // end of member function getModification
    
    /**
     * Saves a logbook record
     * 
     * @param Egwbase_Timemachine_Model_ModificationLog _modification 
     * @return void
     */
    public function setModification( Egwbase_Timemachine_Model_ModificationLog $_modification ) {
        if ($_modification->isValid()) {
            $modificationArray = $_modification->toArray(true);
            
            $application = Egwbase_Application::getInstance()->getApplicationByName($_modification->application);
            $modificationArray['application'] = $application->getId();
            
            $modificationId = $this->_table->insert($modificationArray);
        } else {
            throw new Exception(
                "_modification data is not valid! \n" . 
                print_r($_modification->getValidationErrors(), true)
            );
        }
    } // end of member function setModification
    
    /**
     * Temporary function: Setup timemachine_modificationlog sql table
     *
     */
    protected function setupTable()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $db->getConnection()->exec("CREATE TABLE " . $this->_tablename . " (
            `identifier` INT(11) NOT NULL auto_increment,
            `application` INT(11) NOT NULL,
            `record_identifier` int(11) NOT NULL,
            `record_type` VARCHAR(64),
            `record_backend` VARCHAR(64) NOT NULL,
            `modification_time` DATETIME NOT NULL,
            `modification_account` int(11) NOT NULL,
            `modified_attribute` VARCHAR(64) NOT NULL,
            `modified_from` LONGTEXT,
            `modified_to` LONGTEXT,
            PRIMARY KEY  (`identifier`),
            UNIQUE KEY `history_modification` (`application`,`record_identifier`,
                `record_type`, `record_backend`, `modification_time`, 
                `modification_account`, `modified_attribute`)) 
            ENGINE=MyISAM DEFAULT CHARSET=utf8"
        );
    }
} // end of Egwbase_Timemachine_ModificationLog
?>
