<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * class Tinebase_Record_PersistentObserver
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_PersistentObserver
{

    /**
     * Holds instance for SQL_TABLE_PREFIX . 'record_persistentobserver' table
     * 
     * @var Tinebase_Db_Table
     */
    protected $_db;
    
    /* holds the instance of the singleton
     *
     * @var Tinebase_Record_PersistentObserver
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        // temporary on the fly creation of table
        Tinebase_Setup_SetupSqlTables::createPersistentObserverTable();
        $this->_db = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'record_persistentobserver',
            'primary' => 'identifier'
        ));
        
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Record_PersistentObserver
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Record_PersistentObserver();
        }
        
        return self::$instance;
    }
    
    /**
     *
     * @param Tinebase_Record_Interface $_observable 
     * @param Tinebase_Event_Abstract $_event 
     */
    public function fireEvent( $_observable,  $_event ) {
        $observers = $this->getObserversByEvent($_observable, $_event);
        foreach ($observers as $observer) {
            $controllerName = $observer->observer_application . '_Controller';
            
            if(!class_exists($controllerName)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " No such application controller: '$controllerName'");
                continue;
            }
            
            if(!class_exists($_event)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " No such event: '$_event'");
                continue;
            }
            
            try {
                $controller = call_user_func(array($controllerName, 'getInstance'));
            } catch (Exception $e) {
                // application has no controller or is not useable at all
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Can't get instance of $controllerName : $e");
                continue;
            }
            
            $eventObject = new $_event();
            
            // Tinebase_Model_PersistentObserver holds observer and observable
            $eventObject->observable = $observer;
            
            $controller->handleEvent($eventObject);
        }
    } // end of member function fireEvent

    /**
     * registers new persistent observer
     * 
     * @param Tinebase_Model_PersistentObserver $_persistentObserver 
     * @return Tinebase_Model_PersistentObserver the new persistentObserver
     */
    public function addObserver( $_persistentObserver ) {
        if ($_persistentObserver->getId()) {
            throw new Tinebase_Exception_Record_NotAllowed('Could not add existing observer');
        }
        
        $_persistentObserver->created_by = Tinebase_Core::getUser()->getId();
        $_persistentObserver->creation_time = Tinebase_DateTime::now();
        
        if ($_persistentObserver->isValid()) {
            $data = $_persistentObserver->toArray();
            
            // resolve apps
            $application = Tinebase_Application::getInstance();
            $data['observable_application'] = $application->getApplicationByName($_persistentObserver->observable_application)->id;
            $data['observer_application']   = $application->getApplicationByName($_persistentObserver->observer_application)->id;
            
            $identifier = $this->_db->insert($data);

            $persistentObserver = $this->_db->fetchRow( "identifier = $identifier");
            
            return new Tinebase_Model_PersistentObserver($persistentObserver->toArray(), true);
            
        } else {
            throw new Tinebase_Exception_Record_Validation('some fields have invalid content');
        }
    } // end of member function addObserver

    /**
     * unregisters a persistaent observer
     * 
     * @param Tinebase_Model_PersistentObserver $_persistentObserver 
     * @return void 
     */
    public function removeObserver( $_persistentObserver ) {
        if ($_persistentObserver->getId() && $_persistentObserver->isValid()) {
            $where = array(
                $this->_db->quoteIdentifier('identifier') . ' = ' . $_persistentObserver->getId()
            );
            
            $this->_db->update(array(
                'is_deleted'   => true,
                'deleted_by'   => Tinebase_Core::getUser()->getId(),
                'deleted_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ), $where);
        }
    } // end of member function removeObserver

    /**
     * unregisters all observables of a given observer 
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @return void
     */
    public function removeAllObservables( $_observer ) {
        if ($_observer->getApplication() && $_observer->getId()) {
            $where = array(
                $this->_db->quoteIdentifier('observer_application') . ' =' . $_observer->getApplication(),
                $this->_db->quoteIdentifier('observer_identifier') . '  =' . $_observer->getId()
            );
            
            $this->_db->update(array(
                'is_deleted'   => true,
                'deleted_by'   => Tinebase_Core::getUser()->getId(),
                'deleted_time' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ), $where);
        } else {
            throw new Tinebase_Exception_Record_DefinitionFailure();
        }
    } // end of member function removeAllObservables

    /**
     * returns all observables of a given observer
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_PersitentObserver
     */
    public function getAllObservables( $_observer ) {
        if ($_observer->getApplication() && $_observer->getId()) {
            $where = array(
                $this->_db->quoteIdentifier('observer_application') . ' =' . $_observer->getApplication(),
                $this->_db->quoteIdentifier('observer_identifier') . '  =' . $_observer->getId()
            );
            
            return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_db->fetchAll($where), true);
        } else {
            throw new Tinebase_Exception_Record_DefinitionFailure();
        }
    } // end of member function getAllObservables

    /**
     * returns all observables of a given event and observer
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @param string _event 
     * @return Tinebase_Record_RecordSet
     */
    public function getObservablesByEvent( $_observer,  $_event ) {
        if (!$_observer->getApplication() || !$_observer->getId()) {
            throw new Tinebase_Exception_Record_DefinitionFailure();
        } 
        
        $where = array(
            $this->_db->quoteIdentifier('observer_application') . ' =' . $_observer->getApplication(),
            $this->_db->quoteIdentifier('observer_identifier') . '  =' . $_observer->getId(),
            $this->_db->quoteIdentifier('observed_event') . '       =' . $this->_db->getAdapter()->quote($_event)
        );
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_db->fetchAll($where), true);
    } // end of member function getObservablesByEvent


    /**
     * returns all observers of a given observable and event
     * 
     * @param Tinebase_Record_Interface $_observable 
     * @param string _event 
     * @return Tinebase_Record_RecordSet
     */
    protected function getObserversByEvent( $_observable,  $_event ) {
        if (!$_observer->getApplication() || !$_observer->getId()) {
            throw new Tinebase_Exception_Record_DefinitionFailure();
        }
        
        $where = array(
            $this->_db->quoteIdentifier('observable_application') . ' =' . $_observable->getApplication(),
            $this->_db->quoteIdentifier('observable_identifier') . '  =' . $_observable->getId(),
            $this->_db->quoteIdentifier('observed_event') . '         =' . $this->_db->getAdapter()->quote($_event)
        );
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_db->fetchAll($where), true);
    } // end of member function getObserversByEvent




} // end of Tinebase_Record_PersistentObserver
?>
