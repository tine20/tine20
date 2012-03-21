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
 * File backend for keeping track of remaining tasks.
 *
 * @category   Zend
 * @package    Zend_Scheduler
 * @copyright  Copyright (c) 2006 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Scheduler_Backend_Db extends Zend_Scheduler_Backend_Abstract
{
    /**
     * Default Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'scheduler';
    
    /**
     * DB ressource
     *
     * @var mixed $_db
     */
    protected $_dbAdapter = null;

    /**
     * Constructor
     * 
     * @param array $options Backend options
     */ 
    public function __construct(array $options = array())
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }
    
    /**
     * Initialize table name
     *
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;
    }
    
    /**
     * get table name
     *
     * @return 
     */
    public function getTableName()
    {
        return $this->_tableName;
    }
    
    /**
     * Initialize DbAdapter name
     *
     * @return void
     */
    public function setDbAdapter($dbAdapter)
    {
        $this->_dbAdapter = $dbAdapter;
    }
    
    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDbAdapter()
    {
        return $this->_dbAdapter;
    }
    
    /**
     * Sets the remaining tasks to perform.
     * 
     * @param array $tasks Remaining tasks
     */ 
    public function saveQueue($tasks = array())
    {
        $db = $this->getDbAdapter();
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
        
        try {
            $db->delete($this->getTableName());
            
            foreach ($tasks as $name => $task) {
                
                $requests = array();
                foreach ($task->getRequests() as $request) {
                    $requests[] = array(
                        'controller'    => $request->getControllerName(),
                        'action'        => $request->getActionName(),
                        'params'        => $request->getUserParams()
                    );
                }
                
                $data = array(
                    'months'     =>    $task->getRule('months')->getValue(),
                    'weekdays'   =>    $task->getRule('weekdays')->getValue(),
                    'days'       =>    $task->getRule('days')->getValue(),
                    'hours'      =>    $task->getRule('hours')->getValue(),
                    'minutes'    =>    $task->getRule('minutes')->getValue(),
                    'requests'   =>    $requests
                );
                
                $db->insert($this->getTableName(), array('name' => $name, 'data' => Zend_Json::encode($data)));
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
    
    /**
     * Gets the remaining tasks to perform.
     * 
     * @return array Remaining tasks
     */ 
    public function loadQueue()
    {
        $select = $this->getDbAdapter()->select();
        
        $select->from($this->getTableName());
        $stmt = $this->getDbAdapter()->query($select);
        $result = $stmt->fetchAll();
        
        if (empty($result)) {
            return array();
        }
                
        $class = $this->getTaskClass();
        
        foreach ($result as $item) {
            $data = Zend_Json::decode($item['data']);
            $taskClass = new $class($data);
            foreach ($data['requests'] as $request) {
                $taskClass->addRequest($request['controller'], $request['action'], $request['params']);
            }
            $tasks[$item['name']] = $taskClass;
        }
        
        if (!is_array($tasks)) {
            return array();
        }
        
        return $tasks;
    }

    /**
     * Clears all remaining tasks in the queue.
     */
    public function clearQueue()
    {
        $this->saveQueue();
    }
}
