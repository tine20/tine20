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
    protected static $_requiredApplications = array('Admin', 'Addressbook');
        
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
            self::$_instance = new Crm_Setup_DemoData;
        }

        return self::$_instance;
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Sales_Controller_Contract::getInstance();
        $f = new Sales_Model_ContractFilter(array(
            array('field' => 'lead_name', 'operator' => 'equals', 'value' => 'Relaunch Reseller Platform')
        ));
        return ($c->search($f)->count() > 0) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate() {
        $currentUser = Tinebase_Core::getUser();
        
        $this->_getDays();
        $this->_sharedTaskContainer = $this->_createSharedContainer('Tasks aus Leads', array('application_id' => Tinebase_Application::getInstance()->getApplicationByName('tasks')->getId()), false);
        
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
            'priority'             => 'NORMAL',
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
     * creates shared leads
     */
    protected function _createSharedLeads()
    {
        $this->_createSharedContainer(static::$_de ? 'Gemeinsame Leads' : 'Shared Leads');
        $wed2weeksago = clone $this->_lastWednesday;
        $wed2weeksago->subWeek(1);
        
        $this->_createLead(array(
            'lead_name'     => 'Relaunch Reseller Platform',
            'status'        => 'ACCEPTED',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'container_id'  => $this->_sharedContainer->getId(),
            'start'         => Tinebase_DateTime::now()->subMonth(2),
            'responsible'   => $this->_personas['pwulf'],
            'tasks' => array(
                array('percent' => 100, 'due' => $wed2weeksago, 'summary' => 'Alpha Test', 
                      'organizer' => $this->_personas['jsmith']
                ),
                array('percent' => 100, 'due' => $this->_lastMonday, 'summary' => 'Beta Test', 
                      'organizer' => $this->_personas['jsmith']
                ),
                array('percent' => 100, 'due' => $this->_lastFriday, 'summary' => 'Release Test', 
                      'organizer' => $this->_personas['jsmith'], 'priority' => 'HIGH'
                ),
                array('percent' => 70,  'due' => $this->_nextFriday, 'summary' => 'Pre- Production Tests', 
                      'organizer' => $this->_personas['jsmith'], 'priority' => 'URGENT'
                ),
                array('percent' => 70,  'due' => $this->_nextFriday, 'summary' => 'Erstellung Schulungsunterlagen', 
                      'organizer' => $this->_personas['rwright'], 'priority' => 'LOW'
                ),
                array('percent' => 0,   'due' => $this->_nextFriday, 'summary' => 'Reseller Newsletter', 
                      'organizer' => $this->_personas['sclever'], 'priority' => 'HIGH'
                ),
                array('percent' => 70,  'due' => $this->_friday2week, 'summary' => 'Projekt Nachbesprechung', 
                      'organizer' => $this->_personas['pwulf'], 'priority' => 'LOW'
                ),
            ),
            'contacts' => array(
                array('user' =>    'pwulf',     'type' => 'RESPONSIBLE'),
                array('user' =>    'jsmith',    'type' => 'RESPONSIBLE'),
                array('user' =>    'rwright',   'type' => 'RESPONSIBLE'),
                array('user' =>    'sclever',   'type' => 'RESPONSIBLE'),
                array('contact' => 'Risa Amin', 'type' => 'CUSTOMER')
            )
        ));
    }

    /**
     * creates leads for pwulf
     */
    protected function _createLeadsForPwulf()
    {
        $this->_createLead(array(
            'lead_name'     => static::$_de ? 'Ballett Magazin - Veröffentlichung' : 'Ballet Magazine - Publishing',
            'leadstate_id' => 2,
            'container_id'  => $this->_containers['pwulf']->getId(),
            'start'         => Tinebase_DateTime::now()->subMonth(2),
            'responsible'   => $this->_personas['pwulf'],
            'tasks' => array(
                array('percent' => 100, 'due' => $this->_lastWednesday, 'summary' => 'Zusammenfassung schreiben', 
                      'organizer' => $this->_personas['pwulf']
                ),
                array('percent' => 100, 'due' => $this->_lastThursday, 'summary' => 'Ausarbeitung Angebot', 
                      'organizer' => $this->_personas['pwulf']
                ),
                array('percent' => 100, 'due' => $this->_lastFriday, 'summary' => 'Ausarbeitung Roadmap', 
                      'organizer' => $this->_personas['pwulf'], 'priority' => 'HIGH'
                ),
                array('percent' => 70,  'due' => $this->_nextFriday, 'summary' => 'Kunden Rücksprache', 
                      'organizer' => $this->_personas['pwulf'], 'priority' => 'URGENT'
                )
            ),
            'contacts' => array(
                array('user' =>    'pwulf',     'type' => 'RESPONSIBLE'),
                array('contact' => 'Risa Amin', 'type' => 'CUSTOMER')
            )
        ));
        
        $this->_createLead(array(
            'lead_name'     => static::$_de ? 'Tier und Haus - Abowerbung' : 'Pet and House - Subscription advertising',
            'leadstate_id' => 3,
            'container_id'  => $this->_containers['pwulf']->getId(),
            'start'         => Tinebase_DateTime::now()->subMonth(2),
            'responsible'   => $this->_personas['pwulf'],
            'tasks' => array(
                array('percent' => 70,  'due' => $this->_nextFriday, 'summary' => 'Prüfung Angebot', 
                  'organizer' => $this->_personas['pwulf'], 'priority' => 'LOW'
                ),
                array('percent' => 0,   'due' => $this->_nextFriday, 'summary' => 'Besprechung Team', 
                  'organizer' => $this->_personas['pwulf'], 'priority' => 'NORMAL'
                ),
                array('percent' => 70,  'due' => $this->_nextFriday, 'summary' => 'Qualitätskontrolle', 
                  'organizer' => $this->_personas['pwulf'], 'priority' => 'LOW'
                ),
            ),
            'contacts' => array(
                array('user' =>    'pwulf',     'type' => 'RESPONSIBLE'),
                array('contact' => 'Carolynn Hinsdale', 'type' => 'CUSTOMER')
            )
        ));
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
