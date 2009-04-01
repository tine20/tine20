<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Cli.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
 * 
 * @todo        remove deprecated functions from old http setup?
 */

/**
 * http server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Setup_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Setup';
    
    /**
     * Returns all JS files which must be included for Setup
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Setup/js/init.js',
            'Setup/js/Setup.js',
            'Setup/js/MainScreen.js',
            'Setup/js/ApplicationGridPanel.js',
            'Setup/js/EnvCheckGridPanel.js',
            'Setup/js/ConfigManagerGridPanel.js',
        );
    }
    
    
    /**
     * renders the tine main screen 
     */
    public function mainScreen()
    {
        //$this->checkAuth();
        
        $view = new Zend_View();
        $view->setScriptPath('Setup/views');
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }
    
    
    /**
     * returns an array with all css and js files which needs to be included
     * 
     * @return array 
     */
    public static function getAllIncludeFiles() {
        // we start with all Tinebase include files ...
        $tinebase = new Tinebase_Frontend_Http();
        $jsFiles  = $tinebase->getJsFilesToInclude();
        $cssFiles = $tinebase->getCssFilesToInclude();
        
        // ... and add only the setup files
        $setup = new Setup_Frontend_Http();
        return array(
            'js'  => array_merge($jsFiles, $setup->getJsFilesToInclude()),
            'css' => array_merge($cssFiles, $setup->getCssFilesToInclude())
        );
    }
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
     * @return boolean success
     */
    public function handle()
    {
        if ($_REQUEST['method'] == 'Tinebase.getJsTranslations') {
            $locale = Setup_Core::get('locale');
            $translations = Tinebase_Translation::getJsTranslations($locale);
            header('Content-Type: application/javascript');
            die($translations);
        }
        
        //if ($_GET['mode'] == 'beta') {
            return $this->mainScreen();
        //}
        
        // old http setup    
        /*
        $updateDone = $this->_update();
        $this->_install($updateDone);
        */
    }
    
    /**
     * install new applications
     *
     * @param boolean $_updated
     */
    protected function _install($_updated = FALSE)
    {
        $controller = Setup_Controller::getInstance();
        
        if ($_updated || $this->_check()) {
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
                $import->import();
                //$import = new Setup_Import_Egw14();
                //$import->import();
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
        if (!Setup_Core::configFileExists()) {
            echo "config.inc.php not found.<br/>";
            return FALSE;
        }
        if (!Setup_Core::get(Setup_Core::CHECKDB)) {
            echo "No connection to the database. See logfile for more information.<br/>";
        }
        
        $controller = Setup_Controller::getInstance();
        
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
            $messages = $controller->updateApplications($applications);
            echo implode('<br/>', $messages);
        }
        
        echo "Updated " . count($applications) . " applications.<br>";
        return TRUE;        
    }

    /**
     * check environment
     *
     * @return boolean if check is successful
     * 
     * @todo use this in cli as well
     */
    protected function _check()
    {
        $controller = Setup_Controller::getInstance();
        $envCheck = $controller->environmentCheck();
        $success = $envCheck['success'];
        
        if (!$success) {
            foreach ($envCheck['result'] as $result) {
                if (!empty($result['message'])) {
                    echo $result['message'] . '<br/>';
                }
            }
            return $success;
        }
        
        $extCheck = new Setup_ExtCheck('Setup/essentials.xml');
        $extOutput = $extCheck->getOutput();
        echo $extOutput;
        
        $success = ($success && !preg_match("/FAILURE/", $extOutput));
        
        return $success;
    }
}
