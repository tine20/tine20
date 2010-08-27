<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Cli.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
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
        } elseif(isset($_opts->egw14import)) {
            $this->_egw14Import($_opts);
        } elseif(isset($_opts->check_requirements)) {
            $this->_checkRequirements($_opts);
        } elseif(isset($_opts->setconfig)) {
            $this->_setConfig($_opts);
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
     * 
     * @todo add requird version server side
     * 
     * @param $_applications
     * @param $_options
     * @return unknown_type
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
                $password1 = Tinebase_Server_Cli::promptInput('Inital Admin Users Password', TRUE);
                if (! $password1) {
                    echo "error: password must not be empty! exiting \n";
                    exit (1);
                }
                $password2 = Tinebase_Server_Cli::promptInput('Confirm Password', TRUE);
                if ($password1 == $password2) {
                    $_options['adminPassword'] = $password1;
                } else {
                    echo "error: passwords do not match! exiting \n";
                    exit (1);
                }
            }
        }
    }
    
    /**
     * update existing applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _update(Zend_Console_Getopt $_opts)
    {
        $controller = Setup_Controller::getInstance();
        
        if($_opts->update === true) {
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
        
        foreach($applications as $key => &$application) {
            try {
                if(!$controller->updateNeeded($application)) {
                    //echo "Application $application is already up to date! Skipped...\n";
                    unset($applications[$key]);
                }
            } catch (Setup_Exception_NotFound $e) {
              Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to check if an application needs an update:' . $e->getMessage());
              unset($applications[$key]);
            }
        }

        $updatedApps = 0;
        if(count($applications) > 0) {
            $controller->updateApplications($applications);
            $updatedApps++;
        }
        
        echo "Updated " . $updatedApps . " applications.\n";        
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
        Tinebase_User::syncUsers(true);
    }
    
    /**
     * import from egw14
     * 
     * @param Zend_Console_Getopt $_opts
     */
    protected function _egw14Import(Zend_Console_Getopt $_opts)
    {
        list($host, $username, $password, $dbname, $charset) = $_opts->getRemainingArgs();
        
        $egwDb = Zend_Db::factory('PDO_MYSQL', array(
            'host'     => $host,
            'username' => $username,
            'password' => $password,
            'dbname'   => $dbname
        ));
        $egwDb->query("SET NAMES $charset");
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $logger = new Zend_Log($writer);

        $config = new Zend_Config(array());
        
        $importer = new Tinebase_Setup_Import_Egw14($egwDb, $config, $logger);
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
     * do the environment check
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
        if (empty($options['configvalue'])) {
            $errors[] = 'Missing argument: configvalue';
        }
        $configKey = (string)$options['configkey'];
        $configValue = $this->_parseConfigValue($options['configvalue']);
        //Setup_Controller::getInstance()->setConfig()
        //$results = Setup_Controller::getInstance()->checkRequirements();
        if (empty($errors)) {
           Setup_Controller::setConfigOption($configKey, $configValue);
           echo "OK - Updated configuration option $configKey\n";
        } else {
            echo "ERRORS - The following errors occured: \n";
            foreach ($errors as $error) {
                echo "- " . $error . "\n";
            }
        }
    }
    
    /**
     * parse email options
     * 
     * @param array $_options
     * @return array
     * 
     * @todo generalize this to allow to add other options during cli setup
     */
    protected function _parseConfigValue($_value)
    {
        $result = $_value;
        $_value = preg_replace('/\s*/', '', $_value);
        $parts = explode(',', $_value);
        if (count($parts) > 1) {
            $result = array();
            foreach ($parts as $part) {
                if (preg_match('/_/', $part)) {
                    list($key, $sub) = explode('_', $part);
                    list($subKey, $value) = explode(':', $sub);
                    $result[$key][$subKey] = $value;
                } else {
                    list($key, $value) = explode(':', $part);
                    $result[$key] = $value;
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
    		list($key, $value) = explode('=', $arg);
    		$options[$key] = $value;
    	}
    	
    	return $options;
    }
}
