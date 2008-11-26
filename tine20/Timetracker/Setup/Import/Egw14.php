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
 * @todo        finish import script
 * @todo        parse description fields (striptags?)
 * @todo        import timesheets
 * @todo        import subprojects
 * @todo        add relations (between contract and timeaccount)
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
    }
    
    /**
     * all imports 
     *
     */
    public function import()
    {
        echo "Importing projects data from egroupware ...\n";
        $this->importProjects();        
        echo "done with import.\n";
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
            //-- order by number?
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
        // check for subprojects
        $select = $this->_db->select()
            ->from($this->_oldTablePrefix . 'links')
            ->where($this->_db->quoteInto('link_id1 = ?', $_data['pm_id']))
            ->where("link_app1 = 'projectmanager'")
            ->where("link_app2 = 'projectmanager'");
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        if (count($queryResult) > 0) {
            //-- import subprojects
            /*
            foreach ($queryResult as $row) {
                $this->_importProject($row);
            }
            */
        } else {
            // no subprojects
            
            echo "Importing project without subprojects: " . $_data['pm_title'] . ' ' .$_data['pm_id'] . "\n";
            
            // create timeaccount
            $timeaccount = $this->_createTimeaccount($_data);

            // create contract
            $contract = $this->_createContract($_data);        
            
            //-- link contract to timeaccount
            
            //-- add timesheets
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
        
        return Erp_Controller_Contract::getInstance()->create($contract);
    }

    /**
     * create tine timeaccount
     *
     * @param array $_data with egw project data
     * @return Timesheet_Model_Timeaccount
     * 
     * @todo    add more fields
     */
    protected function _createTimeaccount($_data)
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
        
        return Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount);
    }    
}
