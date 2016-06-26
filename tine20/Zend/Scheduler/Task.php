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
 * Automatic scheduler task.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Scheduler_Task implements Zend_Scheduler_Task_Interface
{
    protected $_frontController = null;
    
    /** 
     * @var Zend_Date Request time 
     */
    protected $_time = null;

    /** @var string First time the task is allowed to run */
    protected $_firstRun = '';

    /** @var string Final time the task is allowed to run */
    protected $_finalRun = '';

    /** @var array Array of Zend_Scheduler_Task_Rule_Abstract objects */
    protected $_rules = array();

    /** @var array Array of Zend_Controller_Request_Abstract objects */
    protected $_requests = array();

    /** @var bool Has the task completed execution? */
    protected $_completed = false;

    /** @var array Array of serialized rules */
    protected $_serialized = array();

    /**
     * Constructor.
     * 
     * @param array $options Task options.
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }
    
    /**
     * static task getter
     * 
     * @param  array $options
     * @return Zend_Scheduler_Task
     */
    public static function getTask(array $options = array())
    {
        return new Zend_Scheduler_Task($options);
    }
    
    /**
     * Sets the Task options.
     * 
     * @param array $options Options
     */ 
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }
    
    /**
     * Set the time (by default, the request time).  For testing purposes a
     * different time can be passed in.
     *
     * @param Zend_Date $time
     * @return Zend_Scheduler_Task This instance
     */
    public function setTime($time)
    {
        if (!$time instanceof Zend_Date) {
            $time = new Zend_Date($time);
        }
        $this->_time = $time;

        return $this;
    }

    /**
     * Sets the request.
     *
     * For the first parameter, users can pass in either the name of the 
     * controller or a request object.
     *
     * @param  string|Zend_Controller_Request_Abstract $controller Controller name or request object
     * @param  string $action Action name
     * @param  array $parameters Request parameters
     * @return Zend_Scheduler_Task This instance
     */
    public function setRequest($controller, $action = 'index', array $parameters = array())
    {
        $this->_requests = array();
        return $this->addRequest($controller, $action, $parameters);
    }

    /**
     * Adds a request.
     *
     * For the first parameter, users can pass in either the name of the 
     * controller or a request object.
     *
     * @param  string|Zend_Controller_Request_Abstract $controller Controller name or request object
     * @param  string $action Action name
     * @param  array $parameters Request parameters
     * @return Zend_Scheduler_Task This instance
     */
    public function addRequest($controller, $action = 'index', array $parameters = array())
    {
        if ($controller instanceof Zend_Controller_Request_Abstract) {
            $request = $controller;
        } else {
            $request = new Zend_Controller_Request_Http();
            
            $request->setControllerName($controller)
                    ->setActionName($action)
                    ->setParams($parameters);
        }
        
        $this->_requests[] = $request;
        return $this;
    }

    /**
     * Sets the first time the task is allowed to run.
     *
     * @param  string $time
     * @return Zend_Scheduler_Task This instance
     */
    public function setEarliestRun($time)
    {
        $this->_firstRun = $time;
    }

    /**
     * Sets the final time the task is allowed to run.
     *
     * @param  string $time
     * @return Zend_Scheduler_Task This instance
     */
    public function setLatestRun($time)
    {
        $this->_finalRun = $time;
    }

    /**
     * Sets the months in which the task is scheduled to run.
     *
     * @param  string $months Comma-delimited list of months (e.g., January)
     * @return Zend_Scheduler_Task This instance
     */
    public function setMonths($months)
    {
        return $this->_addRule('months', $months);
    }

    /**
     * Sets the days of the month in which the task is scheduled to run.
     *
     * @param  string $days Comma-delimited list of days (1-31)
     * @return Zend_Scheduler_Task This instance
     */
    public function setDays($days)
    {
        return $this->_addRule('days', $days);
    }

    /**
     * Sets the days of the week in which the task is scheduled to run.
     *
     * @param  string $days Comma-delimited list of days of the week (e.g., Monday) 
     * @return Zend_Scheduler_Task This instance
     */
    public function setWeekdays($weekdays)
    {
        return $this->_addRule('weekdays', $weekdays);
    }

    /**
     * Sets the hours in which the task is scheduled to run.
     *
     * @param  string $days Comma-delimited list of hours (0-23) 
     * @return Zend_Scheduler_Task This instance
     */
    public function setHours($hours)
    {
        return $this->_addRule('hours', $hours);
    }

    /**
     * Sets the minutes in which the task is scheduled to run.
     *
     * @param  string $days Comma-delimited list of minutes (0-59) 
     * @return Zend_Scheduler_Task This instance
     */
    public function setMinutes($minutes)
    {
        return $this->_addRule('minutes', $minutes);
    }

    /**
     * Determines whether a task should be ran or not.
     *
     * @return bool
     */
    public function isScheduled()
    {
        if ($this->_firstRun) {
            $firstRun = new Zend_Date($this->_firstRun, Zend_Date::ISO_8601);
            $tooEarly = $firstRun->getTimestamp() > $this->_time->getTimestamp();
            if ($tooEarly) {
                return false;
            }
        }
        if ($this->_finalRun) {
            $finalRun = new Zend_Date($this->_finalRun, Zend_Date::ISO_8601);
            $tooLate  = $this->_time->getTimestamp() > $finalRun->getTimestamp();
            if ($tooLate) {
                return false;
            }
        }
        foreach ($this->_rules as $rule) {
            if (!$rule->matches($this->_time)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dispatches all requests. 
     *
     * @return array|null Array of Response objects, or null if no tasks
     */
    public function run()
    {
        //$router         = $controller->getRouter();
        
        $controller = $this->getFrontController();
        
        $returnResponse = ($controller) ? $controller->returnResponse(true) : NULL;
        $responses      = array();
        foreach ($this->_requests as $request) {
//            if ($request->getControllerName()) { // Use default router
//                $controller->setRouter(new Zend_Controller_Router_Rewrite());
//            }
//            $request->setPathInfo($request->getModuleName() . '/' . $request->getControllerName() . '/' . $request->getActionName());
//          $controller->setRouter($router);
            
            if ($returnResponse) {
                $responses[] = $controller->dispatch($request);
            }
        }
        $this->_completed = true;

        return $responses;
    }

    /**
     * Returns whether or not the task has completed execution.
     *
     * @return bool True, if task is completed
     */
    public function isCompleted()
    {
        return $this->_completed;
    }

    /**
     * Executes prior to serialization.
     */
    public function __sleep()
    {
        foreach ($this->_rules as $rule) {
            $this->_serialized['rules'][] = serialize($rule);
        }
        foreach ($this->_requests as $request) {
            $this->_serialized['requests'][] = serialize($request);
        }

        return array('_serialized');
    }

    /**
     * Executes following unserialization.
     */
    public function __wakeup()
    {
        if (isset($this->_serialized['rules'])) {
            foreach ($this->_serialized['rules'] as $rule) {
                $this->_rules[] = unserialize($rule);
            }
        }
        if (isset($this->_serialized['requests'])) {
            foreach ($this->_serialized['requests'] as $request) {
                $this->_requests[] = unserialize($request);
            }
        }
        $this->_serialized = array();
    }

    /**
     * Adds a rule.  Called by {@link setMonths()}, {@link setDays()}, etc.
     *
     * @param  string $type Rule type
     * @param  string $rule Rule string
     * @return Zend_Scheduler_Task This instance
     */
    protected function _addRule($type, $rule)
    {
        $this->_rules[$type] = new Zend_Scheduler_Task_Rule($type, $rule);
        return $this;
    }
    
    /**
     * 
     * @return Array of Zend_Scheduler_Task_Rule objects
     */
    public function getRule($type)
    {
        return $this->_rules[$type];
    }
    
    /**
     * 
     * @return Array of Zend_Controller_Request_Http objects
     */
    public function getRequests()
    {
        return $this->_requests;
    }
    
    public function setFrontController($frontController)
    {
        $this->_frontController = $frontController;
    }
    
    public function getFrontController()
    {
        return $this->_frontController;
    }
    
}
