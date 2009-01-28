<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Cli.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
 */

/**
 * http server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Setup_Frontend_Http
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
     */
    public function authenticate($_username, $_password)
    {
        return false;
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function handle()
    {
        $this->_update();
        $this->_install();
    }
    
    /**
     * install new applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _install()
    {
        $controller = new Setup_Controller();
        
        $extCheck = new Setup_ExtCheck('Setup/essentials.xml');
        $extOutput = $extCheck->getOutput();
        echo $extOutput;

        if (!preg_match("/FAILURE/", $extOutput)) {
            $applications = $controller->getInstallableApplications();
            
            foreach($applications as $key => &$application) {
                try {
                    Tinebase_Application::getInstance()->getApplicationByName($key);
                    // application is already installed
                    unset($applications[$key]);
                } catch (Tinebase_Exception_NotFound $e) {
                    // application is not yet installed
                } catch(Zend_Db_Statement_Exception $e) {
                    // base tables not yet installed
                }
            }
            
            $controller->installApplications(array_keys($applications));
            
            if(array_key_exists('Tinebase', $applications)) {
                $import = new Setup_Import_TineInitial();
                //$import = new Setup_Import_Egw14();
                $import->import();
            }
            
            echo "Successfully installed " . count($applications) . " applications.<br/>";   
                 
        } else {
            echo "Extension Check failed. Nothing installed.<br/>";
        }
    }

    /**
     * update existing applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _update()
    {
        $controller = new Setup_Controller();
        
        try {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } catch(Zend_Db_Statement_Exception $e) {
            // application installed at all
            return;
        }
        
        foreach($applications as $key => &$application) {
            if(!$controller->updateNeeded($application)) {
                echo "Application $application is already up to date! Skipped...<br>";
                unset($applications[$key]);
            }
        }

        if(count($applications) > 0) {
            $controller->updateApplications($applications);
        }
        
        echo "Updated " . count($applications) . " applications.<br>";        
    }
}
