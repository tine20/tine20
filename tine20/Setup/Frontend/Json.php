<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        define API
 * @todo        implement functions
 */

/**
 * Setuo json frontend
 *
 * This class handles all requests from cli scripts
 *
 * @package     Setup
 * @subpackage  Frontend
 */
class Setup_Frontend_Json extends Tinebase_Application_Frontend_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Setup';

    /**
     * install new applications
     *
     */
    public function install(/*Zend_Console_Getopt $_opts*/)
    {
        /*
        $controller = new Setup_Controller();
        
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
        
        $controller->installApplications($applications);
        
        if(in_array('Tinebase', $applications)) {
            $import = new Setup_Import_TineInitial();
            //$import = new Setup_Import_Egw14();
            $import->import();
        }

        echo "Successfully installed " . count($applications) . " applications.\n";
        */        
    }

    /**
     * update existing applications
     *
     */
    public function update(/*Zend_Console_Getopt $_opts*/)
    {
        /*
        $controller = new Setup_Controller();
        
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
        */
    }

    /**
     * uninstall applications
     *
     */
    public function uninstall(/*Zend_Console_Getopt $_opts*/)
    {
        /*
        $controller = new Setup_Controller();
        
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
        
        $controller->uninstallApplications($applications);

        echo "Successfully uninstalled " . count($applications) . " applications.\n";       
        */ 
    }

    /**
     * list installed apps
     *
     */
    public function listInstalled()
    {
        /*
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
        */
    }
}
