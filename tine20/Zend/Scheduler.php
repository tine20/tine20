<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Automatic scheduler.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Scheduler
{
    /** @var array Array of backend names */
    static public $availableBackends = array('File', 'Db');

    /** @var Zend_Date Request time */
    protected $_time = null;

    /** @var array Tasks */
    protected $_tasks = array();

    /** @var array Controller */
    protected $_controller = null;

    /** @var int Maximum allowable tasks to run on a given request */
    protected $_limit = 0;

    /** @var Zend_Scheduler_Backend_Abstract Backend */
    protected $_backend = null;

    /**
     * Constructor.
     * 
     * @param Zend_Scheduler_Backend_Abstract|string $backend Backend or name of backend ('File', etc.)
     * @param array $options Backend options
     */
    public function __construct($backend = null, array $options = array())
    {	
		$this->setTime();
        if ($backend !== null) {
            $this->setBackend($backend, $options);
        }
    }

    /**
     * Set the time (by default, the request time).  For testing purposes a
     * different time can be passed in.
     *
     * @param  string $time
     * @return Zend_Scheduler This instance
     */
    public function setTime($time = '')
    {
        if (empty($time)) {
            $time = $_SERVER['REQUEST_TIME'];
        }
        $this->_time = new Zend_Date($time);
        return $this;
    }

    /**
     * Set backend and options.
     *
     * @param Zend_Scheduler_Backend_Abstract|string $backend Backend or name of backend ('File', etc.)
     * @param array $options Backend options
     */
    public function setBackend($backend, array $options = array())
    {
        if (is_string($backend)) {
            $backendName = ucfirst(strtolower($backend));
            if (in_array($backendName, self::$availableBackends)) {
                $class = 'Zend_Scheduler_Backend_' . $backendName;
                Zend_Loader::loadClass($class);
                $backend = new $class($options);
            } else {
                try { // to load user-implemented backend interface
                    Zend_Loader::loadClass($backend);
                    $backend = new $backend($options);
                } catch (Zend_Exception $e) {
                    throw new Zend_Scheduler_Exception('Invalid backend');
                }
            }
        } // $backend is now an object

        if (!$backend instanceof Zend_Scheduler_Backend_Abstract) {
            throw new Zend_Scheduler_Exception('Backend must extend Zend_Scheduler_Backend_Abstract');
        }

        $this->_backend = $backend;
    }

    /**
     * Return used Backend.
     * 
     * 
     * @return Zend_Scheduler_Backend
     */
    public function getBackend()
    {
    	return $this->_backend;
    }
    
    /** 
     * Creates tasks from a Zend_Config configuration file.
     * 
     * Example INI file:
     * <pre>
     * tasks.mytask.rules.months = Jan, Mar-Apr, Sep, Dec
     * tasks.mytask.rules.hours = 8-17
     * tasks.mytask.rules.minutes = 0/5
     * tasks.mytask.requests.myrequest.controller = threads
     * tasks.mytask.requests.myrequest.action = prune
     * tasks.mytask.requests.myrequest.parameters.days = 90
     * </pre>
     * 
     * After you have created a Zend_Config object with the above INI:
     * <code>
     * $scheduler = new Zend_Scheduler();
     * $scheduler->addConfig($config, 'tasks');
     * </code>
     *
     * You may also specify non-default classes with the 'type' keyword. 
     * In this example, that would apply to 'tasks.mytask.type' and 
     * 'tasks.mytask.requests.myrequest.type'.
     * 
     * @param Zend_Config Configuration object
     * @param string Name of the config section containing task definitions
     */
    public function addConfig(Zend_Config $config, $section) 
    {
        if ($config->{$section} === null) {
            throw new Zend_Scheduler_Exception("No task configuration in section '{$section}'");
        }

        // Parse task information
        foreach ($config->{$section} as $taskName => $taskData) {
            $class = isset($taskData->type) ? $taskData->type : 'Zend_Scheduler_Task';
            $task  = new $class();

            // Parse rule information
            foreach ($taskData->rules as $type => $rule) {
                $set = str_replace('_', ' ', strtolower($type));
                $set = str_replace(' ', '', ucwords($set));
                $set = 'set' . $set;
                $task->{$set}($rule);
            }

            // Parse request information
            foreach ($taskData->requests as $requestName => $requestData) {
                if ($requestData->controller === null) {
                    throw new Zend_Scheduler_Exception("No controller specified for request '{$taskName}:{$requestName}'");
                }
                $class      = isset($requestData->type) ? $requestData->type : 'Zend_Controller_Request_Http';
                $request    = new $class();
                $controller = $requestData->controller;
                $action     = isset($requestData->action) ? $requestData->action : null;
                $parameters = isset($requestData->parameters) ? $requestData->parameters->asArray() : array();
                $request->setControllerName($controller)
                        ->setActionName($action)
                        ->setParams($parameters);
                $task->addRequest($request);
            }
            $this->addTask($taskName, $task);
        }

        return $this;
    }

    /**
     * Adds multiple tasks. Useful in combination with caching an 
     * array of tasks with Zend_Cache, for example.
     *
     * @param  array $tasks Array of tasks
     * @return Zend_Scheduler This instance
     */
    public function addTasks(array $tasks = array())
    {
        foreach ($tasks as $name => $task) {
            $this->addTask($name, $task);
        }
        return $this;
    }

    /**
     * Adds a task.
     *
     * @param  string $name Task name
     * @param  Zend_Scheduler_Task $task
     * @return Zend_Scheduler This instance
     */
    public function addTask($name, Zend_Scheduler_Task $task)
    {
        $this->_tasks[$name] = $task;
        return $this;
    }

    /**
     * Checks if scheduler has a task.
     *
     * @param  string $name Task name
     * @return bool True if task by that name has been added
     */
    public function hasTask($name)
    {
        return isset($this->_tasks[$name]);
    }

    /**
     * Removes a task.
     *
     * @param string $name Task name
     */
    public function removeTask($name)
    {
        if ($this->hasTask($name)) {
            unset($this->_tasks[$name]);
        }
    }

    /**
     * Sets the maximum allowable tasks to run on a given request.  To allow 
     * an infinite number of tasks to run, set to 0.
     *
     * @param  int $limit Task execution limit
     * @return Zend_Scheduler This instance
     */
    public function setLimit($limit = 0)
    {
        $this->_limit = (int) $limit;
        return $this;
    }

    /**
     * Executes all scheduled tasks.
     *
     * @return array|null Array of Response objects, or null if no tasks
     */
    public function run()
    {
        if ($this->_limit and $this->_backend === null) {
            throw new Zend_Scheduler_Exception('If a limit is set, a backend must be specified');
        }

        // Load previously queued tasks
        if ($this->_backend) {
            $this->_tasks = array_merge(
                $this->_tasks, 
                $this->_backend->loadQueue()
            );
        }        
        
        if (empty($this->_tasks)) {
            return null;
        }
        
        $responses = array();
        $completed = 0;

        // Execute tasks until limit (if any) is reached
        foreach ($this->_tasks as $name => $task) {
            $task->setTime($this->_time);
            if (($this->_limit == 0 or $completed < $this->_limit) && $task->isScheduled()) {
                $responses[$name] = $task->run();
                $completed++;
            } else {
                break;
            }
        }

        // Save remaining queued items to persistent store
        if ($this->_backend) {
            $this->_backend->saveQueue($this->_tasks);
        }

        return $responses;
    }

    /**
     * Executes prior to serialization.
     */
    public function __sleep()
    {
        throw new Zend_Scheduler_Exception('Only tasks may be serialized');
    }
}
