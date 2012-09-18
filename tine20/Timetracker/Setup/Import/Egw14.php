<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
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
     * limit number of projects/timesheet to import
     *
     * @var integer
     */
    protected $_limit = 0;
    
    /**
     * begin with this project id
     *
     * @var integer
     */
    protected $_beginId = 0;
    
    /**
     * do utf8 encoding
     *
     * @var boolean
     */
    protected $_utf8Encode = TRUE;
    
    /**
     * import categories
     *
     * @var boolean
     */
    protected $_importCategories = FALSE;
    
    /**
     * egw timesheet categories
     *
     * @var array
     */
    protected $_tsCategories = array();
    
    /**
     * timesheet categories that belong to seperate timeaccounts (cat_id => name)
     *
     * @var array
     */
    protected $_newTimeaccountCategories = array(
        41 => 'Initial Load',
        42 => 'Maintenance',
        /*
        27 => 'Implementierung',
        29 => 'Konzeption',
        33 => 'Support',
        30 => 'Vertrieb'
        */
    );
    
    /**
     * unbillable cat id
     *
     * @var integer
     */
    protected $_unbillableCatId = 26;
    
    /**
     * project filter
     *
     * @var array
     */
    protected $_projectFilter = array(
        array(
            'name' => 'pm_number',
            //'operator' => 'not',
            'operator' => 'contains',
            'value' => '^SOW',
        ),
        /*
        array(
            'name' => 'pm_number',
            //'operator' => 'not',
            //'operator' => 'contains',
            'operator' => 'equals',
            //'value' => '^S-AB-42964$'
            'value' => 'S-AB-42964'
        )
        */
    );
    
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
        // get timesheet categories
        if ($this->_importCategories) {
            $this->_tsCategories = $this->_getTimesheetCategories();
        }
        
        echo "Importing custom fields for timesheets data from egroupware ...";
        $this->importTimesheetCustomFields();
        echo "done.\n";
        
        echo "Importing projects data from egroupware ...";
        $this->importProjects();
        echo "done.\n";
        
        foreach ($this->_counters as $key => $value) {
            echo "Imported $value $key \n";
        }
    }
    
    /**
     * get and create timesheet custom fields
     *
     * @todo add more values (options, order, ...)
     */
    public function importTimesheetCustomFields()
    {
        // get all custom fields   
        $select = $this->_db->select()
            ->from($this->_oldTablePrefix . 'timesheet_extra', 'ts_extra_name')
            ->distinct()
            ->group('ts_extra_name');
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        foreach($queryResult as $row) {
            $customField = new Tinebase_Model_CustomField_Config(array(
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
                'name'              => $row['ts_extra_name'],
                'label'             => $row['ts_extra_name'],        
                'model'             => 'Timetracker_Model_Timesheet',
                'type'              => 'textfield',
                'length'            => 256,        
            ));
            
            try {
                Tinebase_CustomField::getInstance()->addCustomField($customField);
            } catch (Zend_Db_Statement_Exception $ze) {
                // ignore duplicates
                if (!preg_match("/SQLSTATE\[23000\]/", $ze->getMessage())) {
                    throw $ze;
                }
            }
        }
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
            ->where($this->_db->quoteInto('pm_id >= ?', $this->_beginId))
            ->order('pm_id')
            ;
            
        if ($this->_limit > 0) {
            $select->limit($this->_limit);
        }
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        echo "Projects to import: " . count($queryResult) . "\n";
        
        foreach ($queryResult as $row) {
            // check filter
            $doImport = TRUE;
            if (!empty($this->_projectFilter)) {
                foreach($this->_projectFilter as $filter) {
                    if (
                        ($filter['operator'] == 'not' 
                            && preg_match('/' . $filter['value'] . '/', $row[$filter['name']]))
                        ||
                        ($filter['operator'] == 'contains' 
                            && !preg_match('/' . $filter['value'] . '/', $row[$filter['name']]))
                        ||
                        ($filter['operator'] == 'equals' && $filter['value'] != $row[$filter['name']])
                       ) {
                        echo "filter not matched for project: " . $row['pm_number'] . $row['pm_title'] . "\n";
                        $doImport = FALSE;
                    }
                }
            }
            
            if ($doImport) {
                $this->_importProject($row);
            }
        }        
    }
    
    /***************************** protected functions ********************************/
    
    /**
     * init the environment
     *
     */
    protected function _init()
    {
        // init environment
        Tinebase_Core::setupConfig();
        Tinebase_Core::setupLogger();
        Tinebase_Core::set('locale', new Zend_Locale('de_DE'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        Tinebase_Core::setupDatabaseConnection();
        Tinebase_Core::setupCache();
    }
    
    /**
     * import single project
     *
     * @param array $_data
     * @param array $_parentData
     */
    protected function _importProject($_data, $_parentData = array())
    {
        // get subprojects
        $select = $this->_db->select()
            ->from(array('projects' => $this->_oldTablePrefix . 'pm_projects'))
            ->joinLeft(
                array('links'  => $this->_oldTablePrefix . 'links'), 
                "(link_app2='projectmanager' AND link_app1='projectmanager' AND link_id2=projects.pm_id)", 
                array()
            )
            ->where($this->_db->quoteInto('link_id1 = ?', $_data['pm_id']));
            
        if ($this->_limit > 0) {
            $select->limit($this->_limit);
        }
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        if (count($queryResult) > 0) {
            // import subprojects
            foreach ($queryResult as $row) {
                $this->_importProject($row, $_data);
            }
        
        } else {
            // no subprojects
            
            if (empty($_parentData)) {
                echo "Importing mainproject: " . $_data['pm_title'] . ' ' .$_data['pm_id'] . "\n";
            } else {
                echo "- Importing subproject: " . $_data['pm_title'] . ' ' .$_data['pm_id'] . "\n";
            }
            
            // add main project title to some (sub?)projects
            $specialReplacements = array(
                'SOW-43474' => 'CMSLite',
                //'SOW-42248' ?
                //'SOW-42246' ?
            );
            foreach ($specialReplacements as $key => $value) {
                if (preg_match("/^" . $key . "/", $_data['pm_number'])) {
                    $_data['pm_title'] = $value . ' ' . $_data['pm_title'];
                }
            }
        
            // create contract
            $contract = $this->_createContract($_data);
            
            // get timesheets
            $timesheets = $this->_getTimesheetsForProject($_data['pm_id']);
             
            // add timesheets and timeaccounts
            $timeaccounts = array();
            foreach ($timesheets as $timesheet) {
                $this->_importTimesheet($timesheet, $_data, $timeaccounts, $contract->getId());
            }
            
            // create timeaccount event if timesheets are empty
            if (empty($timeaccounts)) {
                $this->_createTimeaccount($_data, $contract->getId());
            }
        }        
    }

    /**
     * import timesheet
     *
     * @param array $_timesheet
     * @param array $_projectData
     * @param array $_timeaccounts
     * @param string $_contractId
     */
    protected function _importTimesheet($_timesheet, $_projectData, &$_timeaccounts, $_contractId)
    {
        // scan timesheets and add additional timeaccounts for special categories
        // only do that for SOW-xxx
        if (isset($this->_newTimeaccountCategories[$_timesheet['cat_id']]) 
            && preg_match("/^SOW/", $_projectData['pm_number'])) {
                
            $catName = $this->_newTimeaccountCategories[$_timesheet['cat_id']];
            
            // create new timeaccount
            if (!isset($_timeaccounts[$_timesheet['cat_id']])) {
                echo "   create new timeaccount for category: " . $catName . "\n";
                $data = $_projectData;
                $data['pm_title'] .= ' [' . $catName . ']';
                $_timeaccounts[$_timesheet['cat_id']] = $this->_createTimeaccount($data, $_contractId);
            } 
            $this->_createTimesheet($_timesheet['record'], $_timeaccounts[$_timesheet['cat_id']]->getId());
            
        } elseif (!empty($_parentData) && $_parentData['pm_number'] == 'SOW-42246/0005') {
            // special project number eshop
            if (!isset($_timeaccounts['eshop'])) {
                echo "   create new timeaccount for eshop subprojects\n";
                $data = $_projectData;
                $data['pm_title'] .= ' [E-Shop]';
                $_timeaccounts['eshop'] = $this->_createTimeaccount($data, $_contractId);
            }
            $this->_createTimesheet($_timesheet['record'], $_timeaccounts['eshop']->getId());
            
        } else {
            // create timeaccount
            if (!isset($_timeaccounts['main'])) {
                echo "  create main timeaccount for project\n";
                $_timeaccounts['main'] = $this->_createTimeaccount($_projectData, $_contractId);
            }
            
            // add category name as tag
            if (!empty($this->_tsCategories[$_timesheet['cat_id']])) {
                $tag = new Tinebase_Model_Tag(array(
                    'id'    => $this->_tsCategories[$_timesheet['cat_id']],
                    'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                    'name'  => 'x'
                ));
                $_timesheet['record']->tags = new Tinebase_Record_Recordset('Tinebase_Model_Tag', array($tag));
            }
            
            $this->_createTimesheet($_timesheet['record'], $_timeaccounts['main']->getId());
        }
        
    }
    
    /********************** create records ***********************/
    
    /**
     * create tine contract
     *
     * @param array $_data with egw project data
     * @return Sales_Model_Contract
     * 
     * @todo    add more fields?
     */
    protected function _createContract($_data)
    {
        $contract = new Sales_Model_Contract(array(
            'title'                 => $_data['pm_title'],
            'description'           => $this->_convertDescription($_data['pm_description']),
        ), TRUE);
        
        $this->_counters['contracts']++;
        
        return Sales_Controller_Contract::getInstance()->create($contract);
    }

    /**
     * create tine timeaccount
     *
     * @param array $_data with egw project data
     * @param string $_contractId
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timeaccount
     * 
     * @todo    add members as groups (which?)
     * @todo    add more fields?
     */
    protected function _createTimeaccount($_data, $_contractId)
    {
        $timeaccount = new Timetracker_Model_Timeaccount(array(
            'title'                 => $this->_encode($_data['pm_title']),
            'number'                => $_data['pm_number'],
            'description'           => $this->_convertDescription($_data['pm_description']),
            'budget'                => $_data['pm_planned_budget'],
            'is_open'               => ($_data['pm_status'] == 'archive') ? 0 : 1,
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
            'related_id'             => $_contractId,   
            'related_model'          => 'Sales_Model_Contract',
            'related_backend'        => Sales_Backend_Contract::TYPE,
            'remark'                 => Timetracker_Model_Timeaccount::RELATION_TYPE_CONTRACT
        ));
           
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount);
        
        // add user grants to this timeaccount (container)
        $members = $this->_getProjectMembers($_data['pm_id']);
        echo "    Got " . count($members) . " members for that project.\n";
        foreach ($members as $userId => $role) {
            $timeaccountContainer = Tinebase_Container::getInstance()->getContainerById($timeaccount->container_id);
            
            // add more different grants depending on roles
            switch ($role) {
                case 4:
                    $grants = array(
                        Tinebase_Model_Grants::GRANT_READ
                    );
                    break;
                case 3:
                    $grants = array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_EDIT,
                        Tinebase_Model_Grants::GRANT_ADD,
                    );
                    break;
                case 2:
                    $grants = array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_EDIT,
                        Tinebase_Model_Grants::GRANT_ADD,
                        Tinebase_Model_Grants::GRANT_DELETE,
                    );
                    break;
                case 1:
                    $grants = array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_EDIT,
                        Tinebase_Model_Grants::GRANT_ADD,
                        Tinebase_Model_Grants::GRANT_DELETE,
                        Tinebase_Model_Grants::GRANT_ADMIN
                    );
                    break;
            }
            
            Tinebase_Container::getInstance()->addGrants($timeaccountContainer, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, $userId, $grants, TRUE);
        }
        
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
            echo "  Timesheet invalid: ". print_r($_record->getValidationErrors(), true) . "\n";
            return NULL;
        }

        $this->_counters['timesheets']++;
    }
    
    /**
     * create new tag with rights and context
     *
     * @param array $_catData
     * @return string tag_id
     */
    protected function _createTag($_catData)
    {
        $tags = Tinebase_Tags::getInstance()->searchTags(new Tinebase_Model_TagFilter(array(
            'name' => $this->_encode($_catData['cat_name']),
            'type' => Tinebase_Model_Tag::TYPE_SHARED
        )), new Tinebase_Model_Pagination());
        
        if (count($tags) == 0) {
            $catData = unserialize($_catData['cat_data']);
            
            // create tag
            $sharedTag = new Tinebase_Model_Tag(array(
                'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                'name'  => $this->_encode($_catData['cat_name']),
                'description' => 'Imported timesheet category ' . $this->_encode($_catData['cat_name']),
                'color' => (!empty($catData['color'])) ? $catData['color'] :  '#009B31',                        
            ));
            
            $newTag = Tinebase_Tags::getInstance()->createTag($sharedTag);
            
            // set rights
            $tagRights = new Tinebase_Model_TagRight(array(
                'tag_id'        => $newTag->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'account_id'    => 0,
                'view_right'    => TRUE,
                'use_right'     => TRUE,
            ));
            Tinebase_Tags::getInstance()->setRights($tagRights);
            
            // set context (Timetracker app)
            $tagContext = array(Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId());
            Tinebase_Tags::getInstance()->setContexts($tagContext, $newTag->getId());
            
            $result = $newTag->getId();
        } else {
            $result = $tags[0]->getId();
        }

        // return new tag id
        return $result;
    }
    
    /*************************** get from egw *************************/
    
    /**
     * get members of a egw project
     *
     * @param integer $_projectId
     * @return array of member ids
     * 
     */
    protected function _getProjectMembers($_projectId)
    {
        // get members
        $select = $this->_db->select()
            ->from($this->_oldTablePrefix . 'pm_members')
            ->where($this->_db->quoteInto("pm_id = ?", $_projectId));

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        $result = array();
        foreach ($queryResult as $row) {
            $result[$row['member_uid']] = $row['role_id'];
        }        
        return $result;
    }
    
    /**
     * get all available timesheet categories and create tags for them
     *
     * @return array with categories (id => '')
     */
    protected function _getTimesheetCategories()
    {
        // get categories
        $select = $this->_db->select()
            ->from($this->_oldTablePrefix . 'categories')
            ->where($this->_db->quoteInto("cat_appname = ?", 'timesheet'));

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        $result = array();
        foreach ($queryResult as $row) {
            $result[$row['cat_id']] = 
                (!in_array($row['cat_id'], array_keys($this->_newTimeaccountCategories))) 
                ? $this->_createTag($row)
                : 0;
        }        
        return $result;
    }
    
    /**
     * get timesheets for project
     *
     * @param integer $_projectId
     * @return array with categories and Timetracker_Model_Timesheet records
     * 
     */
    protected function _getTimesheetsForProject($_projectId)
    {
        $select = $this->_db->select()
            ->from(array('timesheets' => $this->_oldTablePrefix . 'timesheet'))
            ->joinLeft(
                array('links'  => $this->_oldTablePrefix . 'links'), 
                "(link_app1='timesheet' AND link_app2='projectmanager' AND link_id1=timesheets.ts_id)", 
                array()
            )
            ->where($this->_db->quoteInto('links.link_id2 = ?', $_projectId))
            ->group('timesheets.ts_id');
            // order by?
            
        if ($this->_limit > 0) {
            $select->limit($this->_limit);
        }
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        echo "  Timesheets to import for project id $_projectId: " . count($queryResult) . "\n";
        
        $timesheets = array();
        foreach ($queryResult as $row) {

            $data = array(
                'account_id'            => $row['ts_owner'],
                //'account_id'            => Tinebase_Core::getUser()->getId(),
                'start_date'            => date("Y-m-d", $row['ts_start'] + 3601),
                'duration'              => $row['ts_duration'],
                'description'           => (!empty($row['ts_description'])) ? $this->_convertDescription($row['ts_description']) : 'not set (imported)',
                'is_cleared'            => 1,
                'is_billable'           => ($row['cat_id'] == $this->_unbillableCatId) ? 0 : 1,
                'billed_in'             => 'imported'
            );
            
            // add custom fields
            $customFields = $this->_getCustomFieldsForTimesheet($row['ts_id']);
            
            // create timesheet record
            $record = new Timetracker_Model_Timesheet(array_merge($data, $customFields), TRUE);
            
            $timesheets[] = array(
                'cat_id' => $row['cat_id'],
                'record' => $record
            );
        }        
        
        return $timesheets;
    }
    
    /**
     * add custom fields to timesheet record
     *
     * @param integer $_oldTsId
     * @return array with custom fields
     */
    protected function _getCustomFieldsForTimesheet($_oldTsId) 
    {
        // get ts custom fields   
        $select = $this->_db->select()
            ->from($this->_oldTablePrefix . 'timesheet_extra')
            ->where($this->_db->quoteInto("ts_id = ?", $_oldTsId));

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();

        $result = array();
        foreach ($queryResult as $row) {
            //print_r($row);
            if (!empty($row['ts_extra_value'])) {
                $result[$row['ts_extra_name']] = $this->_convertDescription($row['ts_extra_value']);
            }
        }        
        return $result;
    }
    
    /***************** helper funcs ***********************/
    
    /**
     * convert to utf8 / decode htmlentities / ...
     *
     * @param string $_description
     * @return string
     */
    protected function _convertDescription($_description)
    {
        $result = strip_tags($this->_encode(html_entity_decode($_description)));
        
        return $result;
    }
    
    /**
     * encode text
     *
     * @param unknown_type $_text
     */
    protected function _encode($_text) {
        
        if ($this->_utf8Encode) {
            return utf8_encode($_text);
        } else {
            return $_text;
        } 
    }
}
