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
 * Tasks Controller for Tasks
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Task extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Tasks';
        $this->_modelName = 'Tasks_Model_Task';
        $this->_backend = Tasks_Backend_Factory::factory(Tasks_Backend_Factory::SQL);
        $this->_currentAccount = Tinebase_Core::getUser();
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }

    /**
     * holds self
     * @var Tasks_Controller_Task
     */
    private static $_instance = NULL;
    
    /**
     * holds backend instance
     * (only sql atm.)
     *
     * @var Tasks_Backend_Sql
     */
    protected $_backend;
    
    /**
     * singleton
     *
     * @return Tasks_Controller_Task
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller_Task();
        }
        return self::$_instance;
    }
    
    /****************************** overwritten functions ************************/

    /**
     * Create a new Task
     *
     * @param   Tinebase_Record_Interface $_task
     * @return  Tasks_Model_Task
     */
    public function create(Tinebase_Record_Interface $_task)
    {
        if (empty($_task->container_id) || (int)$_task->container_id < 0) {
            $_task->container_id = Tasks_Controller::getInstance()->getDefaultContainer()->getId();
        }
        if(empty($_task->class_id)) {
            $_task->class_id = NULL;
        }
        return parent::create($_task);
    }
}
