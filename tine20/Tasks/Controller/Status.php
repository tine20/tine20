<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Tasks Controller (composite)
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Status extends Tinebase_Application_Controller_Abstract
{
    
    /**
     * holds self
     * @var Tasks_Controller_Status
     */
    private static $_instance = NULL;
    
    /**
     * Holds possible states of a task
     *
     * @var Zend_Db_Table_Rowset
     */
    protected $_status;
    
    /**
     * singleton
     *
     * @return Tasks_Controller_Status
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller_Status();
        }
        return self::$_instance;
    }
    
    /**
     * the constructor
     * 
     * init status array
     * 
     * @todo move db query to backend
     */
    protected function __construct()
    {
        $statusTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks_status'));
        $this->_status = new Tinebase_Record_RecordSet('Tasks_Model_Status', $statusTable->fetchAll()->toArray(),  true);
        
        $this->_currentAccount = Zend_Registry::get('currentAccount');
    }
    
    /**
     * returns all possible task status
     * 
     * @return Tinebase_Record_RecordSet of Tasks_Model_Status
     */
    public function getAllStatus() {
        return $this->_status;
    }

    /**
     * get task status array
     * 
     * @param   int $_statusId
     * 
     * @return array of task status with status_id given
     */
    public function getTaskStatus($_statusId) {
        foreach ($this->_status as $status) {
            if ($status->getId() === $_statusId) {
                $status->translate();
                return $status->toArray();
            }
        }
    }   
}
