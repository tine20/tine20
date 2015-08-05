<?php
/**
 * Tine 2.0
 *
 * @package     Sync
 * @author      Philipp Schüle <p.schuele@metaways.de
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class documentation
 *
 * @package     Sync
 */
class Sync 
{
    /**
     * configuration
     * 
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * logger
     * 
     * @var Zend_Log
     */
    protected $_logger = NULL;
    
    /**
     * ldap connector
     * 
     * @var Zend_Ldap
     */
    protected $_ldap = NULL;

   /**
    * the basic group ldap filter (for example the objectclass)
    *
    * default LDAP:  'objectclass=posixgroup'
    * Samba:         'objectclass=group'
    *
    * @var string
    */
    protected $_groupBaseFilter = 'objectclass=group';
    
   /**
    * the basic user ldap filter (for example the objectclass)
    *
    * default LDAP:  'objectclass=posixaccount'
    * Samba:         'objectclass=user'
    *
    * @var string
    */
    protected $_userBaseFilter = 'objectclass=user';

    /**
     * username property
     *
     * default LDAP: 'uid'
     * Samba:        'samaccountname'
     *
     * @var string
     */
    protected $_usernameProperty = 'samaccountname';

    /**
     * uuid property
     *
     * default LDAP: 'entryuuid'
     * Samba:        'objectguid'
     *
     * @var string
     */
    protected $_uuidProperty = 'objectguid';

    /**
    * the tine user backend
    *
    * @var Tinebase_User_Sql
    */
    protected $_tineUserBackend = NULL;
    
    /**
     * the tine group backend
    *
    * @var Tinebase_Group_Sql
    */
    protected $_tineGroupBackend = NULL;
    
    /**
    * the basic user search scope
    *
    * @var integer
    */
    protected $_userSearchScope = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    /**
    * the basic group search scope
    *
    * @var integer
    */
    protected $_groupSearchScope     = Zend_Ldap::SEARCH_SCOPE_SUB;
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_initSettings();
        $this->_initConfig();
        $this->_initLogger();
        $this->_initLdap();
        $this->_initTineBackends();
        
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' init complete');
    }
    
    /**
     * init php settings
     */
    protected function _initSettings()
    {
        error_reporting(E_COMPILE_ERROR | E_CORE_ERROR | E_ERROR | E_PARSE);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        set_error_handler('Tinebase_Core::errorHandler', E_ALL);
        
        ini_set('iconv.internal_encoding', 'utf-8');
    }
    
    /**
     * init config
     */
    protected function _initConfig()
    {
        $configData = include('conf.php');
        if ($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $this->_config = new Zend_Config($configData);
        
        if (! $this->_config->inputfile) {
            die('need inputfile in config');
        }
    }

    /**
     * init config
     */
    protected function _initLogger()
    {
        $this->_logger = new Zend_Log();
        if ($this->_config->logfile) {
            $writer = new Zend_Log_Writer_Stream($this->_config->logfile);
        } else {
            $writer = new Zend_Log_Writer_Null;
        }
        $this->_logger->addWriter($writer);
        
        if ($this->_config->loglevel) {
            $filter = new Zend_Log_Filter_Priority($this->_config->loglevel);
            $this->_logger->addFilter($filter);
        }
    }
    
    /**
     * returns config
     * 
     * @return Zend_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }
    
    /**
     * init ldap
     */
    protected function _initLdap()
    {
        if (! $this->_config->ldap || ! $this->_config->ldap->baseDn) {
            throw new Exception('ldap config section or basedn missing');
        }
        
        $this->_ldap = new Zend_Ldap($this->_config->ldap->toArray());
        $this->_ldap->bind();
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' LDAP initialized');
    }

    /**
    * init tine backends
    */
    protected function _initTineBackends()
    {
        Tinebase_Core::setupConfig();
        Tinebase_Core::setupDatabaseConnection();
        
        $this->_tineUserBackend = new Tinebase_User_Sql();
        $this->_tineGroupBackend = new Tinebase_Group_Sql();
    }
    
    /**
     * sync user/group ids in db dump
     */
    public function doSync()
    {
        $userMapping = $this->_getUserMapping();
        $groupMapping = $this->_getGroupMapping();
        
        if ($this->_config->dryrun) {
            echo "user mapping:\n" . print_r($userMapping, TRUE);
            echo "group mapping:\n" . print_r($groupMapping, TRUE);
        } else {
            $this->_syncIdsInDump(array_merge($userMapping, $groupMapping));
            $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Sync finished.');
        }
    }
    
    /**
     * read ldap / get users from tine an create mapping
     * 
     * @return array
     */
    protected function _getUserMapping()
    {
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Fetching user mapping ...');
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_userBaseFilter)
        );
        $mapping = array();
        $ldapUsers = $this->_ldap->search($filter, $this->_config->ldap->baseDn, $this->_userSearchScope, array('*', '+'));
        foreach ($ldapUsers as $user) {
            $username = $user[$this->_usernameProperty][0];
            $ldapUuid = ($this->_uuidProperty === 'objectguid')
                ? Tinebase_Ldap::decodeGuid($user['objectguid'][0])
                : $user[$this->_uuidProperty][0];

            try {
                $tineUser = $this->_tineUserBackend->getFullUserByLoginName($username);
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' User ' . $username . ': ' . $tineUser->getId() . ' -> ' . $ldapUuid);
                $mapping[$tineUser->getId()] = $ldapUuid;
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' User ' . $username . ' not found.');
            }
        }
        
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Found ' . count($mapping) . ' users for the mapping.');
        $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($mapping, TRUE));
        
        return $mapping;
    }

    /**
     * read ldap / get users and groups from tine an create mapping
     * 
     * @return array
     */
    protected function _getGroupMapping()
    {
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Fetching user mapping ...');
        
        $filter = Zend_Ldap_Filter::andFilter(
            Zend_Ldap_Filter::string($this->_groupBaseFilter)
        );
        $mapping = array();
        $groupNameMapping = ($this->_config->groupNameMapping) ? $this->_config->groupNameMapping->toArray() : array();
        $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' Group name mapping: ' . print_r($groupNameMapping, TRUE));
        $ldapGroups = $this->_ldap->search($filter, $this->_config->ldap->baseDn, $this->_groupSearchScope, array('*', '+'));
        foreach ($ldapGroups as $group) {
            $groupname = (isset($groupNameMapping[$group['cn'][0]])) ? $groupNameMapping[$group['cn'][0]] : $group['cn'][0];
            $ldapUuid = ($this->_uuidProperty === 'objectguid')
                ? Tinebase_Ldap::decodeGuid($group['objectguid'][0])
                : $group[$this->_uuidProperty][0];

            try {
                $tineGroup = $this->_tineGroupBackend->getGroupByName($groupname);
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' Group ' . $groupname . ' (' .$group['cn'][0] . '): ' . $tineGroup->getId() . ' -> ' . $ldapUuid);
                $mapping[$tineGroup->getId()] = $ldapUuid;
            } catch (Tinebase_Exception_Record_NotDefined $tenf) {
                // @todo should be: Tinebase_Exception_NotFound 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' Group ' . $groupname . ' (' .$group['cn'][0] . '): ' . $tenf->getMessage());
            }
        }
        
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Found ' . count($mapping) . ' groups for the mapping.');
        $this->_logger->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($mapping, TRUE));
        
        return $mapping;
    }
    
    /**
     * syncs the ids in a sql dump file
     * 
     * @param array $mapping
     */
    protected function _syncIdsInDump($mapping)
    {
        // use mapping for str_replace
        if (! file_exists($this->_config->inputfile)) {
            die('file does not exist');
        }
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Reading input file: ' . $this->_config->inputfile);
        $input = file_get_contents($this->_config->inputfile);
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Replacing ids ...');
        $output = str_replace(array_keys($mapping), array_values($mapping), $input);
        
        $filename = ($this->_config->outputfile) ? $this->_config->outputfile : 'synced.sql';
        $this->_logger->info(__METHOD__ . '::' . __LINE__ . ' Writing to file: ' . $filename);
        file_put_contents($filename, $output);
    }
}
