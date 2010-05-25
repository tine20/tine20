<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * samba machine controller
 *
 * @todo add a right for workstation admin and check it here

 * @package    Admin
 * @subpackage Samba
 */
class Admin_Controller_SambaMachine extends Tinebase_Controller_Abstract implements Tinebase_Controller_Record_Interface, Tinebase_Controller_SearchInterface
{
    /**
     * @var array
     */ 
    protected $_options = array();

	/**
	 * @var Admin_Backend_SambaMachine_Ldap
	 */
	protected $_backend = NULL;
	
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_SambaMachine
     */
    private static $_instance = NULL;
 
    /**
     * the singleton pattern
     *
     * @return Admin_Controller_SambaMachine
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_SambaMachine();
        }
        
        return self::$_instance;
    }
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        if (!Tinebase_Core::getConfig()->samba) {
            throw new Admin_Exception('No samba settings defined in config.');
        }
        if(Tinebase_User::getConfiguredBackend() != Tinebase_User::LDAP) {
            throw new Admin_Exception('Works only with LDAP user backend.');
        }
        
        $ldapOptions = Tinebase_User::getBackendConfiguration();
        $sambaOptions = Tinebase_Core::getConfig()->samba->toArray();
        
        $options = array_merge($ldapOptions, $sambaOptions);
        
        $options['machineGroup'] = isset($options['machineGroup']) ? $options['machineGroup'] : 'Domain Computers';
         
        $this->_options = $options;

        $this->_currentAccount = Tinebase_Core::getUser();        
        $this->_applicationName = 'Admin';
		
        // we might want to add a factory here when we support multiple backends
		$this->_backend = new Admin_Backend_SambaMachine($this->_options);
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() { }
    
    /*********** get / search / count leads **************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional         $_pagination
     * @param bool                                       $_getRelations
     * @param boolean                                    $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        //$this->checkRight('VIEW_SAMBAMACHINES');
        
        $machines = $this->_backend->search($_filter, $_pagination, $_getRelations, $_onlyIds);
        $this->_stripDollars($machines);

        return $machines;
    }

    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter) 
    {
        //$this->checkRight('VIEW_SAMBAMACHINES');

        return $this->_backend->searchCount($_filter);
    }

    /**
     * get record by id
     *
     * @param  string $_id
     * @return Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id)
    {
        //$this->checkRight('VIEW_SAMBAMACHINES');
        
        $machine = $this->_backend->get($_id);
        $this->_stripDollar($machine);

        return $machine;
    }

    /**
     * Returns a set of records identified by their id's
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet of $this->_modelName
     */
    public function getMultiple($_ids)
    {
        //$this->checkRight('VIEW_SAMBAMACHINES');
        
        $machines = $this->_backend->getMultiple($_ids);
        $this->_stripDollars($machines);

        return $machines;
    }

    /**
     * Gets all entries
     *
     * @param  string $_orderBy Order result by
     * @param  string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC') 
    {
        //$this->checkRight('VIEW_SAMBAMACHINES');

        $machines = $this->_backend->getAll($_orderBy, $_orderDirection);
        $this->_stripDollars($machines);

        return $machines;
    }
    
    /**
     * strips dollar sign from the end of the computername
     *
     * @param Admin_Model_SambaMachine
     */
    protected function _stripDollar($_record)
    {
        $_record->accountLoginName = preg_replace('/\$$/', '', $_record->accountLoginName);
    }
    
    /**
     * strips dollar sign from the end of the computername
     *
     * @param Tinebase_Record_RecordSet
     */
    protected function _stripDollars($_recordSet)
    {
        foreach ($_recordSet as $record) {
            $this->_stripDollar($record);
        }
    }

    /*************** add / update / delete *****************/    

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        //$this->checkRight('MANAGE_SAMBAMACHINES');

        $machineGroup = Tinebase_Group::getInstance()->getGroupByName($this->_options['machineGroup']);
        $_record->accountPrimaryGroup = $machineGroup->getId();

        $this->_setMachineNames($_record);
        
        return $this->_backend->create($_record);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        //$this->checkRight('MANAGE_SAMBAMACHINES');
        
        $this->_setMachineNames($_record);
        
        return $this->_backend->update($_record); 
    }
    
    /**
     * sets the various names in the machine model
     *
     * @param Admin_Model_SambaMachine
     */
    protected function _setMachineNames($_record)
    {
        $computerName = preg_replace('/\$$/', '', $_record->accountLoginName);
        
        $_record->accountLoginName    = $computerName . '$';
        $_record->accountLastName     = $computerName;
        $_record->accountFullName     = 'Computer ' . $computerName;
        $_record->accountDisplayName  = 'Computer ' . $computerName;
    }

    /**
     * update multiple records
     * 
     * @param   Tinebase_Model_Filter_FilterGroup $_filter
     * @param   array $_data
     * @return  integer number of updated records
     */
    public function updateMultiple($_filter, $_data)
    { 
        //$this->checkRight('MANAGE_SAMBAMACHINES');
        
        return $this->_backend->updateMultiple($_filter, $_data);
    }

    /**
     * Deletes a set of records.
     * 
     * @param   array array of record identifiers
     * @return  void
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($_ids)
    {
        //$this->checkRight('MANAGE_SAMBAMACHINES');
        
        return $this->_backend->delete($_ids);
    }

}
