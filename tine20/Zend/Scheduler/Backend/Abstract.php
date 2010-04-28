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
 * Backend for keeping track of remaining tasks.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Scheduler_Backend_Abstract
{
    /**
     * @var string Task Class
     */
    protected $_taskClass = 'Zend_Scheduler_Task';
    
    /**
     * Constructor.
     * 
     * @param array $options Backend options
     */ 
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Sets the backend options.
     * 
     * @param array $options Options
     */ 
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (!method_exists($this, $method)) {
                throw new Zend_Scheduler_Exception("Option '{$option}' does not exist");
            }
            $this->{$method}($value);
        }
    }

    /**
     * Sets the remaining tasks to perform.
     * 
     * @param array $tasks Remaining tasks
     */ 
    abstract public function saveQueue(array $tasks = null); 
 
    /**
     * Gets the remaining tasks to perform.
     * 
     * @return array Remaining tasks
     */ 
    abstract public function loadQueue();

    /**
     * Clears all remaining tasks in the queue.
     */
    abstract public function clearQueue();
    
    /**
     * Set Task Class.
     * 
     * @param string
     */
    public function setTaskClass($taskClass) 
    {
        $this->_taskClass = $taskClass;
    }
    
    /**
     * Gets the task Class.
     * 
     * @return string
     */
    public function getTaskClass() 
    {
        return $this->_taskClass;
    }
    
    /**
     * Return Array.
     * 
     * @param array $tasks
     * @return array
     */
    public function createTasksArray(array $tasks)
    {
        foreach ($tasks as $name => $task) {
            foreach ($task->getRequests() as $request) {
                $requests[] = array(
                    'controller'    => $request->getControllerName(),
                    'action'        => $request->getActionName(),
                    'params'        => $request->getParams()
                );
            }
            $__tasks[] = array(
                'months'     =>    $task->getRule('months')->getValue(),
                'weekdays'   =>    $task->getRule('weekdays')->getValue(),
                'days'       =>    $task->getRule('days')->getValue(),
                'hours'      =>    $task->getRule('hours')->getValue(),
                'minutes'    =>    $task->getRule('minutes')->getValue(),
                'requests'   =>    $requests
            );        
        }
        return $__tasks;
    }
}
