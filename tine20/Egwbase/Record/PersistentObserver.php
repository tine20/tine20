<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Egwbase_Record_PersistentObserver
 */
class Egwbase_Record_PersistentObserver
{

	/**
	 * Holds instance for SQL_TABLE_PREFIX . 'record_persistentobserver' table
	 * 
	 * @var Egwbase_Db_Table
	 */
	protected $_db;
	
	/* holdes the instance of the singleton
     *
     * @var Egwbase_Record_PersistentObserver
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
    	// temporary on the fly creation of table
    	Egwbase_Setup_SetupSqlTables::createPersistentObserverTable();
    	$this->_db = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'record_persistentobserver'));
    	
    }
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Record_PersistentObserver
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Record_PersistentObserver();
        }
        
        return self::$instance;
    }
    
    /**
     *
     * @param Egwbase_Record_Interface $_observable 
     * @param Egwbase_Events_Abstract $_event 
     * @return 
     */
    public function fireEvent( $_observable,  $_event ) {
        
    } // end of member function fireEvent

    /**
     * registers new persistent observer
     * 
     * @param Egwbase_Model_PersistentObserver $_persistentObserver 
     * @return void
     */
    public function addObserver( $_persistentObserver ) {
    	if ($_persistentObserver->getId()) {
    		throw new Egwbase_Record_Exception_NotAllowed('Could not add existing observer');
    	}
    	
    	$_persistentObserver->creator = Zend_Registry::get('currentAccount')->getId();
    	$_persistentObserver->creation_time = Zend_Date::now();
    	
    	if ($_persistentObserver->isValid()) {
            $this->_db->createRow($_persistentObserver->toArray());
    	} else {
    		throw new Egwbase_Record_Exception_Validation('some fields have invalid content');
    	}
    } // end of member function addObserver

    /**
     * unregisters a persistaent observer
     * 
     * @param Egwbase_Model_PersistentObserver $_persistentObserver 
     * @return void 
     */
    public function removeObserver( $_persistentObserver ) {
        if ($_persistentObserver->getId() && $_persistentObserver->isValid()) {
        	$where = array(
        	    'identifier' => $_persistentObserver->getId()
        	);
        	
        	$this->_db->update(array(
        	    'is_deleted'   => true,
        	    'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
        	    'deleted_time' => Zend_Date::now()->getIso()
        	), $where);
        }
    } // end of member function removeObserver

    /**
     * unregisters all observables of a given observer 
     * 
     * @param Egwbase_Record_Interface $_observer 
     * @return void
     */
    public function removeAllObservables( $_observer ) {
    	if ($_observer->getApplication() && $_observer->getId()) {
	        $where = array(
	            'observer_application' => $_observer->getApplication(),
	            'observer_identifier'  => $_observer->getId()
	        );
	        
            $this->_db->update(array(
                'is_deleted'   => true,
                'deleted_by'   => Zend_Registry::get('currentAccount')->getId(),
                'deleted_time' => Zend_Date::now()->getIso()
            ), $where);
    	} else {
    		throw new Egwbase_Record_Exception_DefinitionFailure();
    	}
    } // end of member function removeAllObservables

    /**
     * returns all observables of a given observer
     * 
     * @param Egwbase_Record_Interface $_observer 
     * @return Egwbase_Record_RecordSet of Egwbase_Model_PersitentObserver
     */
    public function getAllObservables( $_observer ) {
    	if ($_observer->getApplication() && $_observer->getId()) {
    		$where = array(
    		    'observer_application' => $_observer->getApplication(),
                'observer_identifier'  => $_observer->getId()
    		);
    		
    		return new Egwbase_Record_RecordSet($this->_db->fetchAll($where), 'Egwbase_Model_PersistentObserver', true); 
    	} else {
    		throw new Egwbase_Record_Exception_DefinitionFailure(); 
    	}
    } // end of member function getAllObservables

    /**
     * returns all observables of a given event and observer
     * 
     * @param Egwbase_Record_Interface $_observer 
     * @param string _event 
     * @return Egwbase_Record_RecordSet
     */
    public function getObservablesByEvent( $_observer,  $_event ) {
    	if (!$_observer->getApplication() || !$_observer->getId()) {
    		throw new Egwbase_Record_Exception_DefinitionFailure();
    	} 
    	
    	$where = array(
    	    'observer_application' => $_observer->getApplication(),
            'observer_identifier'  => $_observer->getId(),
    	    'observed_event'       => $_event
    	);
    	
    	return new Egwbase_Record_RecordSet($this->_db->fetchAll($where), 'Egwbase_Model_PersistentObserver', true);
    } // end of member function getObservablesByEvent


    /**
     * returns all observers of a given observable and event
     * 
     * @param Egwbase_Record_Interface $_observable 
     * @param string _event 
     * @return Egwbase_Record_RecordSet
     */
    protected function getObserversByEvent( $_observable,  $_event ) {
        if (!$_observer->getApplication() || !$_observer->getId()) {
            throw new Egwbase_Record_Exception_DefinitionFailure();
        }
        
        $where = array(
            'observable_application' => $_observable->getApplication(),
            'observable_identifier'  => $_observable->getId(),
            'observed_event'         => $_event
        );
        
        return new Egwbase_Record_RecordSet($this->_db->fetchAll($where), 'Egwbase_Model_PersistentObserver', true);
    } // end of member function getObserversByEvent




} // end of Egwbase_Record_PersistentObserver
?>
