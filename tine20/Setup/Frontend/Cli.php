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
        if(isset($_opts->install)) {
            $this->_install($_opts);
        } elseif(isset($_opts->update)) {
            $this->_update($_opts);
        } elseif(isset($_opts->uninstall)) {
            $this->_uninstall($_opts);
        } elseif(isset($_opts->list)) {
            $this->_listInstalled();
        } elseif(isset($_opts->import_accounts)) {
            $this->_importAccounts($_opts);
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
        $controller->installApplications($applications, $options);
        
        echo "Successfully installed " . count($applications) . " applications.\n";        
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
            if(!$controller->updateNeeded($application)) {
                //echo "Application $application is already up to date! Skipped...\n";
                unset($applications[$key]);
            }
        }

        if(count($applications) > 0) {
            $controller->updateApplications($applications);
        }
        
        echo "Updated " . count($applications) . " applications.\n";        
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
        // import groups
        Tinebase_Group::getInstance()->importGroups();
        
        // import users
        Tinebase_User::getInstance()->importUsers();
        
        // import group memberships
        Tinebase_Group::getInstance()->importGroupMembers();
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
