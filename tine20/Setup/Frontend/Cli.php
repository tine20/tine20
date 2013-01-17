<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add ext check again
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Setup_Frontend_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Setup';

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     * 
     * @return boolean
     */
    public function authenticate($_username, $_password)
    {
        return false;
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @return void
     */
    public function handle(Zend_Console_Getopt $_opts)
    {
        Setup_Core::set(Setup_Core::USER, 'setupuser');
        
        if(isset($_opts->install)) {
            $this->_install($_opts);
        } elseif(isset($_opts->update)) {
            $this->_update($_opts);
        } elseif(isset($_opts->uninstall)) {
            $this->_uninstall($_opts);
        } elseif(isset($_opts->list)) {
            $this->_listInstalled();
        } elseif(isset($_opts->sync_accounts_from_ldap)) {
            $this->_importAccounts($_opts);
        } elseif(isset($_opts->sync_passwords_from_ldap)) {
            $this->_syncPasswords($_opts);
        } elseif(isset($_opts->egw14import)) {
            $this->_egw14Import($_opts);
        } elseif(isset($_opts->check_requirements)) {
            $this->_checkRequirements($_opts);
        } elseif(isset($_opts->setconfig)) {
            $this->_setConfig($_opts);
        } elseif(isset($_opts->create_admin)) {
            $this->_createAdminUser($_opts);
        }
    }
    
    /**
     * install new applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _install(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();
        
        if($_opts->install === true) {
            $applications = $controller->getInstallableApplications();
            $applications = array_keys($applications);
        } else {
            $applications = array();
            $applicationNames = explode(',', $_opts->install);
            foreach($applicationNames as $applicationName) {
                $applicationName = ucfirst(trim($applicationName));
                try {
                    $controller->getSetupXml($applicationName);
                    $applications[] = $applicationName;
                } catch (Setup_Exception_NotFound $e) {
                    echo "Application $applicationName not found! Skipped...\n";
                }
            }
        }
        
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $this->_promptRemainingOptions($applications, $options);
        
        $controller->installApplications($applications, $options);
        
        if (array_key_exists('acceptedTermsVersion', $options)) {
            Setup_Controller::getInstance()->saveAcceptedTerms($options['acceptedTermsVersion']);
        }
        
        echo "Successfully installed " . count($applications) . " applications.\n";
    }

    /**
     * prompt remaining options
     * 
     * @param array $_applications
     * @param array $_options
     * @return void
     * 
     * @todo add required version server side
     */
    protected function _promptRemainingOptions($_applications, &$_options) {
        if (in_array('Tinebase', $_applications)) {
            
            if (! isset($_options['acceptedTermsVersion'])) {
                fwrite(STDOUT, PHP_EOL . file_get_contents(dirname(dirname(dirname(__FILE__))) . '/LICENSE' ));
                $licenseAnswer = Tinebase_Server_Cli::promptInput('I have read the license agreement and accept it (type "yes" to accept)');
                
                
                fwrite(STDOUT, PHP_EOL . file_get_contents(dirname(dirname(dirname(__FILE__))) . '/PRIVACY' ));
                $privacyAnswer = Tinebase_Server_Cli::promptInput('I have read the privacy agreement and accept it (type "yes" to accept)');
            
                if (! (strtoupper($licenseAnswer) == 'YES' && strtoupper($privacyAnswer) == 'YES')) {
                    echo "error: you need to accept the terms! exiting \n";
                    exit (1);
                }
                
                $_options['acceptedTermsVersion'] = 1;
            }
            
            
            // initial username
            if (! isset($_options['adminLoginName'])) {
                $_options['adminLoginName'] = Tinebase_Server_Cli::promptInput('Inital Admin Users Username');
                if (! $_options['adminLoginName']) {
                    echo "error: username must be given! exiting \n";
                    exit (1);
                }
            }
            
            // initial password
            if (! isset($_options['adminPassword'])) {
                $_options['adminPassword'] = $this->_promptPassword();
            }
        }
    }
    
    /**
     * prompt password
     * 
     * @return string
     */
    protected function _promptPassword()
    {
        $password1 = Tinebase_Server_Cli::promptInput('Admin user password', TRUE);
        if (! $password1) {
            echo "Error: Password must not be empty! Exiting ... \n";
            exit (1);
        }
        $password2 = Tinebase_Server_Cli::promptInput('Confirm password', TRUE);
        if ($password1 !== $password2) {
            echo "Error: Passwords do not match! Exiting ... \n";
            exit (1);
        }
        
        return $password1;
    }
    
    /**
     * update existing applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _update(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();
        
        if ($_opts->update === true) {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } else {
            $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
            $applicationNames = explode(',', $_opts->update);
            foreach($applicationNames as $applicationName) {
                $applicationName = ucfirst(trim($applicationName));
                try {
                    $application = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
                    $applications->addRecord($application);
                } catch (Tinebase_Exception_NotFound $e) {
                    //echo "Application $applicationName is not installed! Skipped...\n";
                }
            }
        }
        
        foreach ($applications as $key => &$application) {
            try {
                if (!$controller->updateNeeded($application)) {
                    //echo "Application $application is already up to date! Skipped...\n";
                    unset($applications[$key]);
                }
            } catch (Setup_Exception_NotFound $e) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to check if an application needs an update:' . $e->getMessage());
                unset($applications[$key]);
            }
        }

        $updatecount = 0;
        if (count($applications) > 0) {
            $result = $controller->updateApplications($applications);
            $updatecount = $result['updated'];
        }
        
        echo "Updated " . $updatecount . " applications.\n";
    }

    /**
     * uninstall applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _uninstall(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();
        
        if($_opts->uninstall === true) {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } else {
            $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
            $applicationNames = explode(',', $_opts->uninstall);
            foreach($applicationNames as $applicationName) {
                $applicationName = ucfirst(trim($applicationName));
                try {
                    $application = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
                    $applications->addRecord($application);
                } catch (Tinebase_Exception_NotFound $e) {
                    //echo "Application $applicationName is not installed! Skipped...\n";
                }
            }
        }
        
        $controller->uninstallApplications($applications->name);

        echo "Successfully uninstalled " . count($applications) . " applications.\n";
    }

    /**
     * list installed apps
     *
     */
    protected function _listInstalled()
    {
        try {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } catch (Zend_Db_Statement_Exception $e) {
            echo "No applications installed\n";
            return;
        }
        
        echo "Currently installed applications:\n";
        foreach($applications as $application) {
            echo "* $application\n";
        }
    }
    
    /**
     * import accounts from ldap
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _importAccounts(Zend_Console_Getopt $_opts)
    {
        // disable timelimit during import of user accounts
        Setup_Core::setExecutionLifeTime(0);
        
        // import groups
        Tinebase_Group::syncGroups();
        
        // import users
        $options = array('syncContactData' => TRUE);
        if ($_opts->dbmailldap) {
            $options['ldapplugins'] = array(
                new Tinebase_EmailUser_Imap_LdapDbmailSchema(),
                new Tinebase_EmailUser_Smtp_LdapDbmailSchema()
            );
        }
        Tinebase_User::syncUsers($options);
    }
    
    /**
     * sync ldap passwords
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _syncPasswords(Zend_Console_Getopt $_opts)
    {
        Tinebase_User::syncLdapPasswords();
    }
    
    /**
     * import from egw14
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _egw14Import(Zend_Console_Getopt $_opts)
    {
        $args = $_opts->getRemainingArgs();
        
        if (count($args) < 1 || ! is_readable($args[0])) {
            echo "can not open config file \n";
            echo "see tine20.org/wiki/EGW_Migration_Howto for details \n\n";
            echo "usage: ./setup.php --egw14import /path/to/config.ini (see Tinebase/Setup/Import/Egw14/config.ini)\n\n";
            exit(1);
        }
        
        try {
            $config = new Zend_Config(array(), TRUE);
            $config->merge(new Zend_Config_Ini($args[0]));
            $config = $config->merge($config->all);
        } catch (Zend_Config_Exception $e) {
            fwrite(STDERR, "Error while parsing config file($args[0]) " .  $e->getMessage() . PHP_EOL);
            exit(1);
        }
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $logger = new Zend_Log($writer);
        
        $filter = new Zend_Log_Filter_Priority((int) $config->loglevel);
        $logger->addFilter($filter);
        
        $importer = new Tinebase_Setup_Import_Egw14($config, $logger);
        $importer->import();
    }
    
    /**
     * do the environment check
     *
     * @return array
     */
    protected function _checkRequirements(Zend_Console_Getopt $_opts)
    {
        $results = Setup_Controller::getInstance()->checkRequirements();
        if ($results['success']) {
          echo "OK - All requirements are met\n";
        } else {
          echo "ERRORS - The following requirements are not met: \n";
          foreach ($results['results'] as $result) {
            if (!empty($result['message'])) {
              echo "- " . strip_tags($result['message']) . "\n";
            }
          }
        }
    }
    
    /**
     * set config
     *
     * @return array
     */
    protected function _setConfig(Zend_Console_Getopt $_opts)
    {
        $options = $this->_parseRemainingArgs($_opts->getRemainingArgs());
        $errors = array();
        if (empty($options['configkey'])) {
            $errors[] = 'Missing argument: configkey';
        }
        if (! isset($options['configvalue'])) {
            $errors[] = 'Missing argument: configvalue';
        }
        $configKey = (string)$options['configkey'];
        $configValue = self::parseConfigValue($options['configvalue']);
        
        $applicationName = (isset($options['app'])) ? $options['app'] : 'Tinebase';
        
        if (empty($errors)) {
           Setup_Controller::getInstance()->setConfigOption($configKey, $configValue, $applicationName);
           echo "OK - Updated configuration option $configKey for application $applicationName\n";
        } else {
            echo "ERRORS - The following errors occured: \n";
            foreach ($errors as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
    /**
     * create admin user / activate existing user / allow to reset password
     * 
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo check role by rights and not by name
     */
    protected function _createAdminUser(Zend_Console_Getopt $_opts)
    {
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            die('Install Tinebase first.');
        }
        
        echo "Please enter a username. If the user already exists, he is reactivated and you can reset the password.\n";
        $username = Tinebase_Server_Cli::promptInput('Username');
        $tomorrow = Tinebase_DateTime::now()->addDay(1);
        
        try {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($username);
            echo "User $username already exists.\n";
            Tinebase_User::getInstance()->setStatus($user->getId(), Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
            echo "Activated admin user '$username'.\n";
            
            $expire = Tinebase_Server_Cli::promptInput('Should the admin user expire tomorrow (default: "no", "y" or "yes" for expiry)?');
            if ($expire === 'y' or $expire === 'yes') {
                Tinebase_User::getInstance()->setExpiryDate($user->getId(), $tomorrow);
                echo "User expires tomorrow at $tomorrow.\n";
            }
            
            $resetPw = Tinebase_Server_Cli::promptInput('Do you want to reset the password (default: "no", "y" or "yes" for reset)?');
            if ($resetPw === 'y' or $resetPw === 'yes') {
                $password = $this->_promptPassword();
                Tinebase_User::getInstance()->setPassword($user, $password);
                echo "User password has been reset.\n";
            }
            
            // check admin group membership
            $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
            $memberships = Tinebase_Group::getInstance()->getGroupMemberships($user);
            if (! in_array($adminGroup->getId(), $memberships)) {
                try {
                    Tinebase_Group::getInstance()->addGroupMember($adminGroup, $user);
                    echo "Added user to default admin group\n";
                } catch (Zend_Ldap_Exception $zle) {
                    echo "Could not add user to default admin group: " . $zle->getMessage();
                }
            }
            
            $this->_checkAdminRole($user);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
                die('It is not possible to create a new user with LDAP user backend here.');
            }
            
            // create new admin user that expires tomorrow
            $password = $this->_promptPassword();
            Tinebase_User::createInitialAccounts(array(
                'adminLoginName' => $username,
                'adminPassword'  => $password,
                'expires'        => $tomorrow,
            ));
            echo "Created new admin user '$username' that expires tomorrow.\n";
        }
    }
    
    /**
     * check admin role membership
     * 
     * @param Tinebase_Model_FullUser $user
     */
    protected function _checkAdminRole($user)
    {
        $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->getId());
        $adminRoleFound = FALSE;
        foreach ($roleMemberships as $roleId) {
            $role = Tinebase_Acl_Roles::getInstance()->getRoleById($roleId);
            if ($role->name === 'admin role') {
                $adminRoleFound = TRUE;
                break;
            }
        }
        
        if (! $adminRoleFound) {
            echo "Admin role not found for user " . $user->accountLoginName . ".\n";
            $adminRole = new Tinebase_Model_Role(array(
                'name'                  => 'admin role',
                'description'           => 'admin role for tine. this role has all rights per default.',
            ));
            $adminRole = Tinebase_Acl_Roles::getInstance()->createRole($adminRole);
            Tinebase_Acl_Roles::getInstance()->setRoleMembers($adminRole->getId(), array(
                array(
                    'id'    => $user->getId(),
                    'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, 
                )
            ));
            
            // add all rights for all apps
            $enabledApps = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
            $roleRights = array();
            foreach ($enabledApps as $application) {
                $allRights = Tinebase_Application::getInstance()->getAllRights($application->getId());
                foreach ($allRights as $right) {
                    $roleRights[] = array(
                        'application_id' => $application->getId(),
                        'right'          => $right,
                    );
                }
            }
            Tinebase_Acl_Roles::getInstance()->setRoleRights($adminRole->getId(), $roleRights);
            
            echo "Created admin role for user " . $user->accountLoginName . ".\n";
        }
    }
    
    /**
     * parse options
     * 
     * @param string $_value
     * @return array|string
     */
    public static function parseConfigValue($_value)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_value, TRUE));
        
        $result = array(
            'active' => 1
        );
        
        $_value = preg_replace(array('/\s*/', '/\\\,/'), array('', ';'), $_value);
        $parts = explode(',', $_value);
        foreach ($parts as $part) {
            $part = str_replace(';', ',', $part);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $part);
            if (strpos($part, '_') !== FALSE) {
                list($key, $sub) = preg_split('/_/', $part, 2);
                if (preg_match('/:/', $sub)) {
                    list($subKey, $value) = explode(':', $sub);
                    $result[$key][$subKey] = $value;
                } else {
                    // might be a '_' in the value
                    if (preg_match('/:/', $part)) {
                        $exploded = explode(':', $part);
                        $key = array_shift($exploded);
                        $result[$key] = implode(':', $exploded);
                    } else {
                        throw new Timetracker_Exception_UnexpectedValue('You have an error in the config syntax (":" expected): ' . $part);
                    }
                }
            } else {
                if (strpos($part, ':') !== FALSE) {
                    list($key, $value) = preg_split('/:/', $part, 2);
                    $result[$key] = $value;
                } else {
                    $result = $part;
                }
            }
        }

        return $result;
    }
    
    /**
     * parse remaining args
     * 
     * @param string $_args
     * @return array
     */
    protected function _parseRemainingArgs($_args)
    {
        $options = array();
        foreach ($_args as $arg) {
            if (strpos($arg, '=') !== FALSE) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $arg);
                list($key, $value) = preg_split('/=/', $arg, 2);
                $options[$key] = $value;
            }
        }
        
        return $options;
    }
}
