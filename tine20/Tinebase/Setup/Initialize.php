<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_Initialize extends Setup_Initialize
{
    /**
     * Override method: Tinebase needs additional initialisation
     * 
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     */
    public function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        $this->_initProcedures();

        $this->_setupConfigOptions($_options);
        $this->_setupGroups();
        
        Tinebase_Acl_Roles::getInstance()->createInitialRoles();
        
        parent::_initialize($_application, $_options);
    }
    
    /**
     * This method is necessary only if Oracle is used.
     * @todo discover a way to replace this code for an equivalent in Tinebase_Backend_Sql_Command
     */
    protected function _initProcedures()
    {
        $backend = Setup_Backend_Factory::factory();
        $_db = Tinebase_Core::getDb();
        if ($_db instanceof Zend_Db_Adapter_Oracle) {
            $md5 = "CREATE OR REPLACE
                function md5( input varchar2 ) return sys.dbms_obfuscation_toolkit.varchar2_checksum as
                begin
                    return lower(rawtohex(utl_raw.cast_to_raw(sys.dbms_obfuscation_toolkit.md5( input_string => input ))));
                end;";
            $backend->execQueryVoid($md5);

            $now = "CREATE OR REPLACE
                function NOW return DATE as
                begin
                    return SYSDATE;
                end;";
            $backend->execQueryVoid($now);

            $typeStringAgg = "CREATE OR REPLACE TYPE t_string_agg AS OBJECT
                    (
                      g_string  VARCHAR2(32767),

                      STATIC FUNCTION ODCIAggregateInitialize(sctx  IN OUT  t_string_agg)
                        RETURN NUMBER,

                      MEMBER FUNCTION ODCIAggregateIterate(self   IN OUT  t_string_agg, value  IN      VARCHAR2 )
                         RETURN NUMBER,

                      MEMBER FUNCTION ODCIAggregateTerminate(self         IN   t_string_agg,
                                                             returnValue  OUT  VARCHAR2,
                                                             flags        IN   NUMBER)
                        RETURN NUMBER,

                      MEMBER FUNCTION ODCIAggregateMerge(self  IN OUT  t_string_agg,
                                                         ctx2  IN      t_string_agg)
                        RETURN NUMBER
                    );";
            $backend->execQueryVoid($typeStringAgg);

            $typeStringAgg = "CREATE OR REPLACE TYPE BODY t_string_agg IS
                  STATIC FUNCTION ODCIAggregateInitialize(sctx  IN OUT  t_string_agg)
                    RETURN NUMBER IS
                  BEGIN
                    sctx := t_string_agg(NULL);
                    RETURN ODCIConst.Success;
                  END;

                  MEMBER FUNCTION ODCIAggregateIterate(self   IN OUT  t_string_agg,
                                                       value  IN      VARCHAR2 )
                    RETURN NUMBER IS
                  BEGIN
                    SELF.g_string := self.g_string || ',' || value;
                    RETURN ODCIConst.Success;
                  END;

                  MEMBER FUNCTION ODCIAggregateTerminate(self         IN   t_string_agg,
                                                         returnValue  OUT  VARCHAR2,
                                                         flags        IN   NUMBER)
                    RETURN NUMBER IS
                  BEGIN
                    returnValue := RTRIM(LTRIM(SELF.g_string, ','), ',');
                    RETURN ODCIConst.Success;
                  END;

                  MEMBER FUNCTION ODCIAggregateMerge(self  IN OUT  t_string_agg,
                                                     ctx2  IN      t_string_agg)
                    RETURN NUMBER IS
                  BEGIN
                    SELF.g_string := SELF.g_string || ',' || ctx2.g_string;
                    RETURN ODCIConst.Success;
                  END;
                END;";
            $backend->execQueryVoid($typeStringAgg);

            $group_concat = "CREATE OR REPLACE
                FUNCTION GROUP_CONCAT (p_input VARCHAR2)
                RETURN VARCHAR2
                PARALLEL_ENABLE AGGREGATE USING t_string_agg;";
            $backend->execQueryVoid($group_concat);
        }
    }

    /**
     * set config options (accounts/authentication/email/...)
     * 
     * @param array $_options
     */
    protected function _setupConfigOptions($_options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Saving config options (accounts/authentication/email/...)');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_options, TRUE));
        
        $defaults = empty($_options['authenticationData']) ? Setup_Controller::getInstance()->loadAuthenticationData() : $_options['authenticationData'];
        $defaultGroupNames = $this->_parseDefaultGroupNameOptions($_options);
        $defaults['accounts'][Tinebase_User::getConfiguredBackend()] = array_merge($defaults['accounts'][Tinebase_User::getConfiguredBackend()], $defaultGroupNames);
        
        $emailConfigKeys = Setup_Controller::getInstance()->getEmailConfigKeys();
        $configsToSet = array_merge($emailConfigKeys, array('authentication', 'accounts', 'redirectSettings', 'acceptedTermsVersion'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($configsToSet, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($defaults, TRUE));
        
        $optionsToSave = array();
        foreach ($configsToSet as $group) {
            if (isset($_options[$group])) {
                $parsedOptions = (is_string($_options[$group])) ? Setup_Frontend_Cli::parseConfigValue($_options[$group]) : $_options[$group];
                
                switch ($group) {
                    case 'authentication':
                    case 'accounts':
                        $backend = (isset($parsedOptions['backend'])) ? ucfirst($parsedOptions['backend']) : Tinebase_User::SQL;
                        $optionsToSave[$group][$backend] = (isset($parsedOptions[$backend])) ? $parsedOptions[$backend] : $parsedOptions;
                        $optionsToSave[$group]['backend'] = $backend;
                        break;
                    default:
                        $optionsToSave[$group] = $parsedOptions;
                }
            } else if (isset($defaults[$group])) {
                $optionsToSave[$group] = $defaults[$group];
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($optionsToSave, TRUE));
        
        Setup_Controller::getInstance()->saveEmailConfig($optionsToSave);
        Setup_Controller::getInstance()->saveAuthentication($optionsToSave);
    }
    
    /**
    * Extract default group name settings from {@param $_options}
    *
    * @param array $_options
    * @return array
    */
    protected function _parseDefaultGroupNameOptions($_options)
    {
        $result = array(
            'defaultAdminGroupName' => (isset($_options['defaultAdminGroupName'])) ? $_options['defaultAdminGroupName'] : Tinebase_Group::DEFAULT_ADMIN_GROUP,
            'defaultUserGroupName'  => (isset($_options['defaultUserGroupName'])) ? $_options['defaultUserGroupName'] : Tinebase_Group::DEFAULT_USER_GROUP,
        );
        
        return $result;
    }
    
    /**
     * import groups(ldap)/create initial groups(sql)
     */
    protected function _setupGroups()
    {
        if (Tinebase_User::getInstance() instanceof Tinebase_User_Interface_SyncAble) {
            Tinebase_Group::syncGroups();
        } else {
            Tinebase_Group::createInitialGroups();
        }
    }
    
    /**
     * Override method because this app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     * 
     * @todo make hard coded role name ('user role') configurable
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
        parent::_createInitialRights($_application);

        $roles = Tinebase_Acl_Roles::getInstance();
        $userRole = $roles->getRoleByName('user role');
        $roles->addSingleRight(
            $userRole->getId(), 
            $_application->getId(), 
            Tinebase_Acl_Rights::CHECK_VERSION
        );
        $roles->addSingleRight(
            $userRole->getId(), 
            $_application->getId(), 
            Tinebase_Acl_Rights::REPORT_BUGS
        );
        $roles->addSingleRight(
            $userRole->getId(), 
            $_application->getId(), 
            Tinebase_Acl_Rights::MANAGE_OWN_STATE
        );
    }
    
    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        
        Tinebase_Scheduler_Task::addAlarmTask($scheduler);
        Tinebase_Scheduler_Task::addCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addCredentialCacheCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addTempFileCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addDeletedFileCleanupTask($scheduler);
        Tinebase_Scheduler_Task::addSessionsCleanupTask($scheduler);
    }
}
