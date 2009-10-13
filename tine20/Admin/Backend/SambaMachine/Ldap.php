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
 * samba machine ldap backend
 *
 * @package     Admin
 * @subpackage  Samba
 */
class Admin_Backend_SambaMachine_Ldap implements Tinebase_Backend_Interface
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @var Tinebase_User_Ldap
     */
    protected $_posixBackend = NULL;
    
    /**
     * @var Tinebase_SambaSAM_Ldap
     */
    protected $_samBackend = NULL;
    
    /**
     * constructor
     */
    public function __construct()
    {
            $ldapOptions = Tinebase_User::getBackendConfiguration();
            $machineOptions = Tinebase_Core::getConfig()->samba->toArray();
            $options = array_merge($ldapOptions, $machineOptions);
            
            $options['userDn']    = $machineOptions['ldap']['machineDn'];
            $options['minUserId'] = $machineOptions['minMachineId'];
            $options['maxUserId'] = $machineOptions['maxMachineId'];
            $options['requiredObjectClass'] = array(
                'top',
                'person',
                'posixAccount',
                //'sambaSamAccount'
            );
            
            $this->_options = $options;

            $this->_posixBackend = new Tinebase_User_Ldap($options);
            $this->_samBackend = new Tinebase_SambaSAM_Ldap($options);
    }
    
    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination         $_pagination
     * @param  boolean                           $_onlyIds
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        // we only support query filter atm
        foreach ($_filter as $filterLine) {
            if ($filterLine instanceof Tinebase_Model_Filter_Query) {
                $filterString = $filterLine->getValue();
            }
        }

        $records = $this->_posixBackend->getUsers($filterString, $_pagination->sort, $_pagination->dir, $_pagination->start, $_pagination->limit, 'Admin_Model_SambaMachine');
        
        return $records;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $pagination = new Tinebase_Model_Pagination(array());
        return count($this->search($_filter, $pagination));
    }
    
    /**
     * Return a single record
     *
     * @param string $_id
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        $posixAccount = $this->_posixBackend->getUserById($_id, 'Admin_Model_SambaMachine')->toArray();
        $samAccount = $this->_samBackend->getUserById($_id)->toArray();

        $machine = new Admin_Model_SambaMachine(array_merge($posixAccount, $samAccount));
        
        return $machine;
    }
    
    /**
     * Returns a set of contacts identified by their id's
     * 
     * @param  string|array $_id Ids
     * @param array $_containerIds all allowed container ids that are added to getMultiple query
     * @return Tinebase_RecordSet of Tinebase_Record_Interface
     */
    public function getMultiple($_ids, $_containerIds = NULL)
    {
        throw new Tinebase_Exception_NotImplemented();
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        throw new Tinebase_Exception_NotImplemented();
    }
    
    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $allData = $_record->toArray();

        // we need some handling for the displayname, as this attribute is only in the samba object class or inetOrgPerson ;-(
        $displayName = $allData['accountDisplayName'];
        $posixAccount = new Tinebase_Model_FullUser($allData, true);
        $posixAccount->accountDisplayName = NULL;

        $posixAccount = $this->_posixBackend->addUser($posixAccount);
        

        $samAccount = new Tinebase_Model_SAMUser($allData, true);
        $samAccount->acctFlags       = '[W          ]';
        $samAccount->primaryGroupSID = $this->_samBackend->getGroupById($posixAccount->accountPrimaryGroup)->sid;
        
        $samAccount = $this->_samBackend->addUser($posixAccount, $samAccount);

        // after we saved the samAccount we can also save the displayName
        $posixAccount->accountDisplayName = $displayName;
        $this->_posixBackend->updateUser($posixAccount);

        return $this->get($posixAccount->getId());
    }
    
    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_contact
     * @return Tinebase_Record_Interface|NULL
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $allData = $_record->toArray();
        $posixAccount = new Tinebase_Model_FullUser($allData, true);
        $samAccount = new Tinebase_Model_SAMUser($allData, true);

        $posixAccount = $this->_posixBackend->updateUser($posixAccount);
        $samAccount = $this->_samBackend->updateUser($posixAccount, $samAccount);

        return $this->get($posixAccount->getId());
    }
    
    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     */
    public function updateMultiple($_ids, $_data)
    {
        throw new Tinebase_Exception_NotImplemented();
    }
        
    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return void
     */
    public function delete($_identifier)
    {
        $posixAccount = $this->_posixBackend->deleteUsers((array)$_identifier);
        $samAccount = $this->_samBackend->deleteUsers((array)$_identifier);
    }
    
    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return 'LDAP';
    }
}
