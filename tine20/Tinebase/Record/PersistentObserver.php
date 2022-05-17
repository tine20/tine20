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

    /**
     * @var array
     */
    protected $_eventRecursionPrevention = array();

    /**
     * @var bool
     */
    protected $_outerCall = true;
    
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
     * @param Tinebase_Event_Observer_Abstract $_event
     */
    public function fireEvent(Tinebase_Event_Observer_Abstract $_event)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Fire Event ' . get_class($_event));

        $setOuterCall = false;
        if (true === $this->_outerCall) {
            $this->_eventRecursionPrevention = array();
            $this->_outerCall = false;
            $setOuterCall = true;
        }

        try {
            $observers = $this->getObserversByEvent($_event->observable, get_class($_event));

            /** @var Tinebase_Model_PersistentObserver $observer */
            foreach ($observers as $observer) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Handling event in observer ' . $observer->observer_model);

                $observerId = $observer->getId();
                if (isset($this->_eventRecursionPrevention[$observerId])) {
                    continue;
                }
                $this->_eventRecursionPrevention[$observerId] = true;

                /** @var Tinebase_Controller_Record_Abstract $controller */
                if (!isset($this->_controllerCache[$observer->observer_model])) {
                    try {
                        $controller = Tinebase_Core::getApplicationInstance($observer->observer_model, '', true);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' ' . $tenf->getMessage());
                        continue;
                    }
                    $this->_controllerCache[$observer->observer_model] = $controller;
                } else {
                    $controller = $this->_controllerCache[$observer->observer_model];
                }

                $_event->persistentObserver = $observer;

                $rightsCheck = null;
                $containerAclCheck = null;
                if (!$observer->do_acl) {
                    $rightsCheck = $controller->doRightChecks(false);
                    $containerAclCheck = $controller->doContainerACLChecks(false);
                }

                $controller->handleEvent($_event);

                if (!$observer->do_acl) {
                    $controller->doContainerACLChecks($containerAclCheck);
                    $controller->doRightChecks($rightsCheck);
                }
            }
        } finally {
            $this->_outerCall = $setOuterCall;
            if (true === $this->_outerCall) {
                $this->_eventRecursionPrevention = array();
            }
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
            $data['do_acl'] = (isset($data['do_acl']) && false === $data['do_acl']) ? 0 : 1;
            
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
     * Remove observer by it's identifier
     *
     * @param $identifier
     */
    public function removeObserverByIdentifier($identifier)
    {
        $where = array(
            $this->_db->quoteIdentifier('observer_identifier') . ' = ' . $this->_db->quote($identifier)
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
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote((string)$_observer->getId())
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
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote((string)$_observer->getId())
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
            $this->_db->quoteIdentifier('observer_identifier') .  ' = ' . $this->_db->quote((string)$_observer->getId()),
            $this->_db->quoteIdentifier('observed_event') .       ' = ' . $this->_db->quote((string)$_event)
        );
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_table->fetchAll($where)->toArray(), true);
    }

    /**
     * returns all observables of a given observer model
     *
     * @param string $_model
     * @return Tinebase_Record_RecordSet of Tinebase_Model_PersistentObserver
     */
    public function getObservablesByObserverModel($_model)
    {
        $where = array(
            $this->_db->quoteIdentifier('observer_model') .       ' = ' . $this->_db->quote($_model)
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
        $where =
            $this->_db->quoteIdentifier('observable_model') .      ' = ' . $this->_db->quote(get_class($_observable)) . ' AND (' .
            $this->_db->quoteIdentifier('observable_identifier') . ' = ' . $this->_db->quote((string)$_observable->getId()) . ' OR ' .
            $this->_db->quoteIdentifier('observable_identifier') . ' IS NULL ) AND ' .
            $this->_db->quoteIdentifier('observed_event') .        ' = ' . $this->_db->quote((string)$_event)
        ;

        return new Tinebase_Record_RecordSet('Tinebase_Model_PersistentObserver', $this->_table->fetchAll($where)->toArray(), true);
    }

    /**
     * @param Tinebase_Model_Application $_application
     * @return int
     */
    public function deleteByApplication(Tinebase_Model_Application $_application)
    {
        $sqlCommand = Tinebase_Backend_Sql_Command::factory($this->_db);
        $likeQName = $sqlCommand->getLike() . $this->_db->quote($_application->name . '%');

        $where =
            $this->_db->quoteIdentifier('observable_model') . ' ' . $likeQName . ' OR ' .
            $this->_db->quoteIdentifier('observer_model')   . ' ' . $likeQName;

        return $this->_table->delete($where);
    }
}