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
        $updateDone = $this->_update();
        $this->_install($updateDone);
    }
    
    /**
     * install new applications
     *
     * @param Zend_Console_Getopt $_opts
     */
    protected function _install($_updated = FALSE)
    {
        $controller = new Setup_Controller();
        
        if (!$_updated) {
            $check = $this->_check();
        }

        if ($_updated || $check) {
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
            echo "Extension / Environment Check failed. Nothing installed.<br/>";
        }
    }

    /**
     * update existing applications
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean update done
     */
    protected function _update()
    {
        $controller = new Setup_Controller();
        
        try {
            $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
        } catch(Zend_Db_Statement_Exception $e) {
            // application installed at all
            return FALSE;
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
        return TRUE;        
    }

    /**
     * check environment
     *
     * @return boolean if check is successful
     * 
     * @todo use this in cli as well (move to controller?)
     */
    protected function _check()
    {
        $success = TRUE;
        
        // check php environment
        $requiredIniSettings = array(
            'magic_quotes_sybase'  => 0,
            'magic_quotes_gpc'     => 0,
            'magic_quotes_runtime' => 0,
            'mbstring.func_overload' => 0,
            'eaccelerator.enable' => 0,
            'memory_limit' => '128M'
        );
        
        foreach ($requiredIniSettings as $variable => $newValue) {
            $oldValue = ini_get($variable);
            if ($variable == 'memory_limit') {
                $required = intval(substr($newValue,0,strpos($newValue,'M')));
                $set = intval(substr($oldValue,0,strpos($oldValue,'M')));  
                if ( $set < $required) {
                    echo "Sorry, your environment is not supported. You need to set $variable equal or greater than $newValue (now: $oldValue).";
                    $success = FALSE;
                }
                //echo $variable . ": " . $newValue . " " . $oldValue;
            } elseif ($oldValue != $newValue) {
                if (ini_set($variable, $newValue) === false) {
                    echo "Sorry, your environment is not supported. You need to set $variable from $oldValue to $newValue.";
                    $success = FALSE;
                }
            }
        }
        
        $extCheck = new Setup_ExtCheck('Setup/essentials.xml');
        $extOutput = $extCheck->getOutput();
        echo $extOutput;
        
        $success = ($success && preg_match("/FAILURE/", $extOutput));
        
        return $success;
    }
}
