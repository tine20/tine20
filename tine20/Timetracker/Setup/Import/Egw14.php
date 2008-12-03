<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 * @todo        get timesheet custom fields
 * @todo        create additional timeaccounts for special (egw-)timesheet categories
 * @todo        parse description fields (striptags?)
 * @todo        import all relevant record fields
 * @todo        add db transaction?
 * @todo        remove limit() in sql stmts
 * @todo        finish import script
 */

/**
 * class to import project/timesheet data from egw14
 * 
 * @package     Timetracker
 * @subpackage  Setup
 */
class Timetracker_Setup_Import_Egw14
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /**
     * old table prefix
     * 
     * @var string
     */
    protected $_oldTablePrefix = "egw_";
                        
    /**
     * counter for the imported objects
     *
     * @var array
     */
    protected $_counters = array();
    
    /**
     * the constructor 
     *
     * @param   string $_importAccountName [OPTIONAL]
     */
    public function __construct($_importAccountName = 'tine20admin')
    {
        $this->_init();       

        // set import user current account
        $account = Tinebase_User::getInstance()->getFullUserByLoginName($_importAccountName);
        Tinebase_Core::set(Tinebase_Core::USER, $account);
        
        $this->_db = Tinebase_Core::getDb();
        
        $this->_counters = array (
            'contracts' => 0,
            'timeaccounts' => 0,
            'timesheets' => 0,
        );
    }
    
    /**
     * all imports 
     *
     */
    public function import()
    {
        echo "Importing projects data from egroupware ...\n";
        
        //-- get+save timesheet custom fields
        
        $this->importProjects();
                
        echo "done with import.\n";
        
        foreach ($this->_counters as $key => $value) {
            echo "Imported $value $key \n";
        }
    }
    
    /**
     * init the environment
     *
     */
    protected function _init()
    {
        // init environment
        Tinebase_Core::setupConfig();
        Tinebase_Core::setupServerTimezone();
        Tinebase_Core::setupLogger();
        Tinebase_Core::setupCache();
        Tinebase_Core::set('locale', new Zend_Locale('de_DE'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        Tinebase_Core::setupDatabaseConnection();        
    }
    
    /**
     * import egw projects
     *
     */
    public function importProjects()
    {
        // get all main projects
        $select = $this->_db->select()
            ->from(array('projects' => $this->_oldTablePrefix . 'pm_projects'))
            ->joinLeft(
                array('links'  => $this->_oldTablePrefix . 'links'), 
                "(link_app2='projectmanager' AND link_app1='projectmanager' AND link_id2=projects.pm_id)", 
                array()
            )
            ->where('links.link_id2 IS NULL')
            //->limit(10)
            ->limit(5)
            //-- order by project number?
            ;
            
        //echo $select->__toString();
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        echo "Projects to import: " . count($queryResult) . "\n";
        
        foreach ($queryResult as $row) {
            $this->_importProject($row);
        }        
    }
    
    /**
     * import single project
     *
     * @param array $_data
     */
    protected function _importProject($_data, $_parentId = 0)
    {
        // get subprojects
        $select = $this->_db->select()
            ->from(array('projects' => $this->_oldTablePrefix . 'pm_projects'))
            ->joinLeft(
                array('links'  => $this->_oldTablePrefix . 'links'), 
                "(link_app2='projectmanager' AND link_app1='projectmanager' AND link_id2=projects.pm_id)", 
                array()
            )
            ->where($this->_db->quoteInto('link_id1 = ?', $_data['pm_id']))
            ->limit(5)
        ;

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        if (count($queryResult) > 0) {
            // import subprojects
            foreach ($queryResult as $row) {
                $this->_importProject($row, $_data['pm_id']);
            }
        
        } else {
            // no subprojects
            if ($_parentId == 0) {
                echo "Importing mainproject: " . $_data['pm_title'] . ' ' .$_data['pm_id'] . "\n";
            } else {
                echo "- Importing subproject: " . $_data['pm_title'] . ' ' .$_data['pm_id'] . "\n";
            }
            
            // create contract
            $contract = $this->_createContract($_data);        
            
            // create timeaccount
            // @todo put timeaccounts for this project in an array
            $timeaccount = $this->_createTimeaccount($_data, $contract);

            // get timesheets
            $timesheets = $this->_getTimesheetsForProject($_data['pm_id']);
             
            // add timesheets
            foreach ($timesheets as &$timesheet) {
                // @todo scan timesheets and add additional timeaccounts for special categories
                
                $timesheet = $this->_createTimesheet($timesheet, $timeaccount->getId());
            }
        }        
    }
    
    /**
     * create tine contract
     *
     * @param array $_data with egw project data
     * @return Erp_Model_Contract
     * 
     * @todo    add more fields
     */
    protected function _createContract($_data)
    {
        $contract = new Erp_Model_Contract(array(
            'title'                 => $_data['pm_title'],
            'description'           => $_data['pm_description'],
        //-- add modlog info?
        ), TRUE);
        
        $this->_counters['contracts']++;
        
        return Erp_Controller_Contract::getInstance()->create($contract);
    }

    /**
     * create tine timeaccount
     *
     * @param array $_data with egw project data
     * @param Erp_Model_Contract $_contract
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timeaccount
     * 
     * @todo    add more fields
     */
    protected function _createTimeaccount($_data, $_contract)
    {
        $timeaccount = new Timetracker_Model_Timeaccount(array(
            'title'                 => $_data['pm_title'],
            'number'                => $_data['pm_number'],
            'description'           => $_data['pm_description'],
            'budget'                => $_data['pm_planned_budget'],
            'is_open'               => ($_data['pm_status'] == 'archive') ? 0 : 1,
        //-- add modlog info?
        /*
            'unit'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
            'unitprice'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
            
        */        
        ), TRUE);
        
        // link contract to timeaccount
        $timeaccount->relations = array(array(
            'own_model'              => 'Timetracker_Model_Timeaccount',
            'own_backend'            => Timetracker_Backend_Timeaccount::TYPE,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'type'                   => Timetracker_Model_Timeaccount::RELATION_TYPE_CONTRACT,
            'related_id'             => $_contract->getId(),   
            'related_model'          => 'Erp_Model_Contract',
            'related_backend'        => Erp_Backend_Contract::TYPE     
        ));
           
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount);
        
        $this->_counters['timeaccounts']++;
        
        return $timeaccount;
    }    

    /**
     * create tine timesheet
     *
     * @param Timetracker_Model_Timesheet $_record
     * @param string $_timeaccountId
     * @return Timetracker_Model_Timesheet 
     * 
     */
    protected function _createTimesheet($_record, $_timeaccountId)
    {
        $_record->timeaccount_id = $_timeaccountId;
        
        // check if user is available
        try {
            Tinebase_User::getInstance()->getUserById($_record->account_id);
        } catch (Tinebase_Exception_NotFound $enf) {
            //echo "  Couldn't find user with id " . $_record->account_id . "\n";
            return NULL;
        }
        
        if ($_record->isValid()) {
            Timetracker_Controller_Timesheet::getInstance()->create($_record);
        } else {
            //echo "  Timesheet invalid: ". print_r($_record->getValidationErrors(), true) . "\n";
            return NULL;
        }

        $this->_counters['timesheets']++;
    }
    
    /**
     * get timesheets for project
     *
     * @param integer $_projectId
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timesheet records
     * 
     * @todo add correct account id again
     */
    protected function _getTimesheetsForProject($_projectId)
    {
        $select = $this->_db->select()
            ->from(array('timesheets' => $this->_oldTablePrefix . 'timesheet'))
            ->joinLeft(
                array('links'  => $this->_oldTablePrefix . 'links'), 
                "(link_app1='timesheet' AND link_app2='projectmanager' AND link_id2=timesheets.ts_id)", 
                array()
            )
            ->where($this->_db->quoteInto('links.link_id2 = ?', $_projectId))
            //->limit(10)
            //-- order by?
            ;
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        echo "  Timesheets to import for project id $_projectId: " . count($queryResult) . "\n";
        
        $timesheets = new Tinebase_Record_RecordSet('Timetracker_Model_Timesheet');
        foreach ($queryResult as $row) {
            
            //-- get custom fields
            
            // create timesheet record
            $record = new Timetracker_Model_Timesheet(array(
                //'account_id'            => $row['ts_owner'],
                'account_id'            => Tinebase_Core::getUser()->getId(),
                'start'                 => $row['ts_start'],
                'duration'              => $row['ts_duration'],
                'description'           => $row['ts_description'],
                'is_cleared'            => 1,
                //'timeaccount_id'        => ,
                //'is_billable'           => ,
            ), TRUE);
            
            $timesheets->addRecord($record);
        }        
        
        return $timesheets;
    }
}
