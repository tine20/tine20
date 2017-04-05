<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_table;

    /**
     * @var Zend_Db
     */
    protected $_db;

    /**
     * @var array
     */
    protected $_controllerCache = array();
    
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
        $this->_table = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'record_observer',
            'primary' => 'id'
        ));
        $this->_db = $this->_table->getAdapter();
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
     * @param string $_event
     */
    public function fireEvent(Tinebase_Record_Interface $_observable, $_event)
    {
        if (!class_exists($_event)) {
            throw new Tinebase_Exception_InvalidArgument('Event ' . $_event . ' doesn\'t exist');
        }

        $observers = $this->getObserversByEvent($_observable, $_event);
        /** @var Tinebase_Event_Observer_Abstract $eventObject */
        $eventObject = new $_event();
        $eventObject->observable = $_observable;

        /** @var Tinebase_Model_PersistentObserver $observer */
        foreach ($observers as $observer) {

            /** @var Tinebase_Controller_Record_Abstract $controller */

            if (!isset($this->_controllerCache[$observer->observer_model])) {
                $controller = Tinebase_Core::getApplicationInstance($observer->observable_model);
                $this->_controllerCache[$observer->observer_model] = $controller;
            } else {
                $controller = $this->_controllerCache[$observer->observer_model];
            }

            $eventObject->persistentObserver = $observer;
            
            $controller->handleEvent($eventObject);
        }
    }

    /**
     * registers new persistent observer
     *
     * @param Tinebase_Model_PersistentObserver $_persistentObserver
     * @return Tinebase_Model_PersistentObserver the new persistentObserver
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     */
    public function addObserver(Tinebase_Model_PersistentObserver $_persistentObserver) {
        if (null !== $_persistentObserver->getId()) {
            throw new Tinebase_Exception_Record_NotAllowed('Can not add existing observer');
        }
        
        $_persistentObserver->created_by = Tinebase_Core::getUser()->getId();
        $_persistentObserver->creation_time = Tinebase_DateTime::now();
        
        if ($_persistentObserver->isValid()) {
            $data = $_persistentObserver->toArray();
            
            $identifier = $this->_table->insert($data);

            $persistentObserver = $this->_table->fetchRow("id = $identifier");
            
            return new Tinebase_Model_PersistentObserver($persistentObserver->toArray(), true);
            
        } else {
            throw new Tinebase_Exception_Record_Validation('some fields have invalid content');
        }
    }

    /**
     * unregisters a persistent observer
     * 
     * @param Tinebase_Model_PersistentObserver $_persistentObserver 
     * @return void 
     */
    public function removeObserver(Tinebase_Model_PersistentObserver $_persistentObserver)
    {
        $where = array(
            $this->_db->quoteIdentifier('id') . ' = ' . (int)$_persistentObserver->getId()
        );

        $this->_table->delete($where);
    }

    /**
     * unregisters all observables of a given observer 
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @return void
     */
    public function removeAllObservables(Tinebase_Record_Interface $_observer)
    {
        $where = array(
            $this->_db->quoteIdentifier('observer_model') .       ' = ' . $this->_db->quote(get_class($_observer)),
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote($_observer->getId())
        );

        $this->_table->delete($where);
    }

    /**
     * returns all observables of a given observer
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @return Tinebase_Record_RecordSet of Tinebase_Model_PersistentObserver
     */
    public function getAllObservables(Tinebase_Record_Interface $_observer)
    {
        $where = array(
            $this->_db->quoteIdentifier('observer_model') .       ' = ' . $this->_db->quote(get_class($_observer)),
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote($_observer->getId())
        );

        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_table->fetchAll($where)->toArray(), true);
    }

    /**
     * returns all observables of a given event and observer
     * 
     * @param Tinebase_Record_Interface $_observer 
     * @param string $_event
     * @return Tinebase_Record_RecordSet of Tinebase_Model_PersistentObserver
     */
    public function getObservablesByEvent(Tinebase_Record_Interface $_observer, $_event)
    {
        $where = array(
            $this->_db->quoteIdentifier('observer_model') .       ' = ' . $this->_db->quote(get_class($_observer)),
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote($_observer->getId()),
            $this->_db->quoteIdentifier('observed_event') .       ' = ' . $this->_db->quote($_event)
        );
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_table->fetchAll($where)->toArray(), true);
    }


    /**
     * returns all observers of a given observable and event
     * 
     * @param Tinebase_Record_Interface $_observable
     * @param string $_event
     * @return Tinebase_Record_RecordSet of Tinebase_Model_PersistentObserver
     */
    protected function getObserversByEvent(Tinebase_Record_Interface $_observable,  $_event)
    {
        $where = array(
            $this->_db->quoteIdentifier('observable_model') .       ' = ' . $this->_db->quote(get_class($_observable)),
            $this->_db->quoteIdentifier('observable_identifier') .  ' = ' . $this->_db->quote($_observable->getId()),
            $this->_db->quoteIdentifier('observed_event') .         ' = ' . $this->_db->quote($_event)
        );

        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_table->fetchAll($where)->toArray(), true);
    }
}