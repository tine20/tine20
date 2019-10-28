<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Crm initialization
 *
 * @package     Setup
 */
class Crm_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Crm_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'Crm';
    
    /**
     * models to work on
     * @var unknown_type
     */
    protected $_models = array('lead');

    /**
     * 
     * required apps
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'Addressbook', 'Sales');
        
    /**
     * private containers
     * 
     * @var Array
     */
    protected $_calendars = array();
    
    /**
     * holds the shared container
     */
    protected $_sharedContainer     = NULL;
    protected $_sharedTaskContainer = NULL;
    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Crm_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Crm_Controller_Lead::getInstance();
        $f = new Crm_Model_LeadFilter(array(
            array('field' => 'lead_name', 'operator' => 'equals', 'value' => 'Relaunch Reseller Platform')
        ));
        
        return ($c->search($f)->count() > 0);
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate() {
        $currentUser = Tinebase_Core::getUser();
        
        $this->_getDays();
        $this->_sharedTaskContainer = $this->_createSharedContainer('Tasks aus Leads', array('application_id' => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId()), false);
        
        $fe = new Tinebase_Frontend_Json();
        $fe->savePreferences(array(
            "Tasks" => array(
                "defaultTaskList"               => array('value' => $this->_sharedTaskContainer->getId()),
                "defaultpersistentfilter"       => array('value' => '_default_')
            )
        ), true);
        
        foreach($this->_personas as $loginName => $persona) {
            $this->_containers[$loginName] = Tinebase_Container::getInstance()->getContainerById(Tinebase_Core::getPreference('Crm')->getValueForUser(Crm_Preference::DEFAULTLEADLIST, $persona->getId()));
            Tinebase_Container::getInstance()->addGrants($this->_containers[$loginName]->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
            Tinebase_Container::getInstance()->addGrants($this->_containers[$loginName]->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);
        }
    }
    
    /**
     * creates a lead
     */
    protected function _getLead($a, $tasks, $contacts, $username)
    {
        $controller = Crm_Controller_Lead::getInstance();
        $controller->sendNotifications(0);
        
        $defaults = array(
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
        );
        
        $lead = $controller->create(
            new Crm_Model_Lead(array_merge($defaults, $a))
        );
        
        $relations = array();
        
        if ($tasks) {
            foreach($tasks as $taskArray) {
                
                $task = $this->_getTask($taskArray);
                $relations[] = $this->_getRelationArray($lead, $task);
            }
        }
        
        if ($contacts) {
            foreach($contacts as $contactArray) {
                $contact = $this->_getContact($contactArray);
                $relations[] = $this->_getRelationArray($lead, $contact, 'sibling', $contactArray['type']);
            }
        }
        
        $lead->relations = $relations;
        $lead = $controller->update($lead);
        $controller->sendNotifications(1);
        
        return $lead;
    }
    

    /**
     * creates a task by the given data
     */
    protected function _getTask($data)
    {
        $defaults = array(
            // tine record fields
            'priority'             => Tasks_Model_Priority::NORMAL,
            'percent'              => 70,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            'summary'              => 'demo task',
            'container_id'         => $this->_sharedTaskContainer->getId()
        );
        
        // create test task
        $task = new Tasks_Model_Task(array_merge($defaults, $data));
        $tc = Tasks_Controller_Task::getInstance();
        $tc->doContainerACLChecks(false);
        $task = $tc->create($task);
        return $task;
    }

    /**
     * creates a lead
     */
    protected function _createLead($data, $username = 'shared')
    {
        $tasks = NULL;
        if ((isset($data['tasks']) || array_key_exists('tasks', $data))) {
            $tasks = $data['tasks'];
            unset($data['tasks']);
        }
        $contacts = NULL;
        if ((isset($data['contacts']) || array_key_exists('contacts', $data))) {
            $contacts = $data['contacts'];
            unset($data['contacts']);
        }
        
        $lead = $this->_getLead($data, $tasks, $contacts, $username);
        
        return $lead;
    }
    
    /**
     * creates many leads
     */
    protected function _createLeads($leads, $username = 'shared')
    {
        foreach($leads as $lead) {
            $this->_createLead($lead, $username);
        }
    }

    /**
     * 
     * @param Addressbook_Model_Contact $organizer
     * @param Integer $state
     * @param integer $index
     * @return array
     */
    protected function _generateTasks($organizer, $state, $index)
    {
        if ($state == 0) {
            $status = 'IN-PROCESS';
        } elseif ($state > 0) {
            $status = 'NEEDS-ACTION';
        } else {
            $status = 'COMPLETED';
        }
        
        $tasks = array();
        
        $tasks[] = array(
            'percent' => ($state == 0) ? 90 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextMonday,
            'summary' => 'Alpha Test',
            'organizer' => $organizer
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 70 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextThursday, 
            'summary' => 'Beta Test',
            'organizer' => $organizer
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 90 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextTuesday, 
            'summary' => 'Release Test',
            'organizer' => $organizer ,
            'priority' => Tasks_Model_Priority::HIGH
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 80 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextFriday, 
            'summary' => 'Pre- Production Tests',
            'organizer' => $organizer,
            'priority' => Tasks_Model_Priority::URGENT
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 50 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextFriday, 
            'summary' => 'Erstellung Schulungsunterlagen',
            'organizer' => $organizer,
            'priority' => Tasks_Model_Priority::LOW
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 10 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextWednesday, 
            'summary' => 'Newsletter',
            'organizer' => $organizer,
            'priority' => Tasks_Model_Priority::HIGH
        );
        $tasks[] = array(
            'percent' => ($state == 0) ? 20 : (($state < 0) ? 100 : 0),
            'due' => $this->_nextMonday, 
            'summary' => 'Projektierung',
            'organizer' => $organizer,
            'priority' => Tasks_Model_Priority::LOW
        );
        
        for ($i = 0; $i < (count($tasks) - 1); $i++) {
            $tasks[$i]['status'] = $status;
        }
        
        return $tasks;
    }
    
    /**
     * creates shared leads
     */
    protected function _createSharedLeads()
    {
        $contactController = Addressbook_Controller_Contact::getInstance();

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => 'contact'),
        ));
        $pagination = new Tinebase_Model_Pagination();
        $pagination->start = 0;
        $pagination->limit = 100;
        $addresses = $contactController->search($filter, $pagination);

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => 'user'),
        ));
        $users = $contactController->search($filter);
        
        $userids = $users->getId();

        
        // remove admin user
        $currentUser = $users->getById(Tinebase_Core::getUser()->contact_id);

        if ($currentUser) {
            $users->removeRecord($currentUser);
        }
        
        $userCount = $users->count();
        
        $lastOrgName = NULL;
        $lead = NULL;
        
        $startDate = Tinebase_DateTime::now()->subWeek(10);
        // first day is a monday
        while($startDate->format('w') != 1) {
            $startDate->subDay(1);
        }
        
        $this->_createSharedContainer(static::$_de ? 'Gemeinsame Leads' : 'Shared Leads');
        
        $controller = Crm_Controller_Lead::getInstance();
        $controller->sendNotifications(0);
        
        $orgNames = array_unique($addresses->org_name);
        shuffle($orgNames);
        
        $i = 0;
        $userIndex = -1;
        $state = -1;
        $stateIndex = 0;
        
        foreach($orgNames as $orgName) {
            if (empty($orgName)) {
                continue;
            }
            
            $orgAddresses = $addresses->filter('org_name', $orgName);
            
            if ($orgAddresses->count() < 2) {
                continue;
            }
            
            $userIndex++;
            
            while (! $user = $users->getById($userids[$userIndex])) {
                $userIndex++;
                if ($userIndex >= ($userCount - 1)) {
                    $userIndex = 0;
                }
                
            }
            
            if ($i%2 == 0) {
                // create more running leads
                if (($state == 0) && ($stateIndex < 5)) {
                    $stateIndex++;
                } else {
                    $stateIndex = 0;
                    $startDate->addWeek(1);
                }
            }
            
            // date is the startdate of the lead, always monday, we want friday in a week
            $due = clone $startDate;
            $due->addWeek(1)->addDay(4);
            
            $this->_getDays($due);
            $now = new Tinebase_DateTime();
            
            if ($startDate < $now && $due > $now) {
                $state = 0;
            } elseif ($startDate > $now) {
                $state = 1;
            } else {
                $state = -1;
            }
            
            $lead = $controller->create(new Crm_Model_Lead(array(
                'lead_name'     => $orgName,
                'leadstate_id'  => ($state > 0) ? 5 : 1,
                'leadtype_id'   => rand(1, 3),
                'leadsource_id' => rand(1, 4),
                'container_id'  => $this->_sharedContainer->getId(),
                'start'         => $startDate,
                'end'           => ($state < 0) ? $due : NULL,
                'end_scheduled' => $due,
                'probability'   => ($state > 0) ? 50 + ($userIndex*10) : 100,
                'turnover'      => (($i+2)^5)*1000
            ))
            );
            
            $relations = array();
            
            foreach($orgAddresses as $address) {
                $relations[] = $this->_getRelationArray($lead, $address, 'sibling', 'CUSTOMER');
            }
            
            $relations[] = $this->_getRelationArray($lead, $user, 'sibling', 'RESPONSIBLE');
            
            $tasks = $this->_generateTasks($user, $state, $i);
            
            foreach($tasks as $taskArray) {
                $task = $this->_getTask($taskArray);
                $relations[] = $this->_getRelationArray($lead, $task);
            }

            $lead->relations = $relations;
            $controller->update($lead);
            
            $i++;
        }
    }

    /**
     * creates leads for pwulf
     */
    protected function _createLeadsForPwulf()
    {
    }
    
    /**
     * creates leads for jsmith
     */
    protected function _createLeadsForJsmith()
    {
    }

    /**
     * creates leads for rwright
     */
    protected function _createLeadsForRwright()
    {
    }

    /**
     * creates leads for sclever
     */
    protected function _createLeadsForSclever()
    {
    }

    /**
     * creates leads for jmcblack
     */
    protected function _createLeadsForJmcblack()
    {
    }
}
