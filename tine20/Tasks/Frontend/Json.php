<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use functions from Tinebase_Frontend_Json_Abstract
 */


/**
 * json interface for tasks
 * @package     Tasks
 */
class Tasks_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Tasks';
    
    /**
     * user timezone
     *
     * @var string
     */
    protected $_userTimezone;
    
    /**
     * server timezone
     *
     * @var string
     */
    protected $_serverTimezone;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_userTimezone = Tinebase_Core::getUserTimezone();
        $this->_serverTimezone = date_default_timezone_get();
    }

    /**
     * Search for tasks matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchTasks($filter, $paging)
    {
        return parent::_search($filter, $paging, Tasks_Controller_Task::getInstance(), 'Tasks_Model_TaskFilter', TRUE);
    }
    
    /**
     * Return a single Task
     *
     * @param string $id
     * @return Tasks_Model_Task task
     */
    public function getTask($id)
    {
        return $this->_get($id, Tasks_Controller_Task::getInstance());
    }

    /**
     * creates/updates a Task
     *
     * @param  array $recordData
     * @return array created/updated task
     */
    public function saveTask($recordData)
    {
        if (empty($recordData['status'])) {
            // sanitize status - client allows to send empty status
            $recordData['status'] = 'NEEDS-ACTION';
        }
        return $this->_save($recordData, Tasks_Controller_Task::getInstance(), 'Task');
    }
    
    /**
     * Deletes an existing Task
     *
     * @param array $ids 
     * @return string
     */
    public function deleteTasks($ids)
    {
        return $this->_delete($ids, Tasks_Controller_Task::getInstance());
    }
    
    /**
     * temporaray function to get a default container
     * 
     * @return array container
     */
    public function getDefaultContainer()
    {
        $container = Tasks_Controller::getInstance()->getDefaultContainer();
        $container->setTimezone($this->_userTimezone);
        return $container->toArray();
    }
    
    /**
     * Returns registry data of the tasks application.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $defaultContainer = Tasks_Controller::getInstance()->getDefaultContainer();
        $defaultContainerArray = $defaultContainer->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainer)->toArray();
        
        
        $registryData = array(
            'defaultContainer' => $defaultContainerArray
        );
        
        return $registryData;
    }
}
