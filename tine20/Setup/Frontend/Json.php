<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add tests
 * @todo        add ext/environment check
 */

/**
 * Setup json frontend
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
     * setup controller
     *
     * @var Setup_Controller
     */
    protected $_controller = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_controller = new Setup_Controller();
    }
    
    /**
     * install new applications
     *
     * @param string $applicationNames application names to install
     */
    public function install($applicationNames)
    {
        $this->_controller->installApplications(Zend_Json::decode($applicationNames));

        if(in_array('Tinebase', $applications)) {
            $import = new Setup_Import_TineInitial();
            //$import = new Setup_Import_Egw14();
            $import->import();
        }
    }

    /**
     * update existing applications
     *
     * @param string $applicationNames application names to update
     */
    public function update($applicationNames)
    {
        $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        foreach (Zend_Json::decode($applicationNames) as $applicationName) {
            $applications->addRecord(Tinebase_Application::getInstance()->getApplicationByName($applicationName));
        }
        
        if(count($applications) > 0) {
            $this->_controller->updateApplications($applications);
        }
    }

    /**
     * uninstall applications
     *
     * @param string $applicationNames application names to uninstall
     */
    public function uninstall($applicationNames)
    {
        $applications = new Tinebase_Record_RecordSet('Tinebase_Model_Application');
        foreach (Zend_Json::decode($applicationNames) as $applicationName) {
            $applications->addRecord(Tinebase_Application::getInstance()->getApplicationByName($applicationName));
        }
        
        if(count($applications) > 0) {
            $this->_controller->uninstallApplications($applications);
        }
    }
    
    /**
     * search for installed and installable applications
     *
     * @return array
     */
    public function search()
    {
        // get installed apps
        $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id')->toArray();
        foreach ($applications as &$application) {
            $application['current_version'] = $this->_controller->getSetupXml($application['name'])->version;
        }
        
        // get installable apps
        $installable = $this->_controller->getInstallableApplications();
        foreach ($installable as $name => $setupXML) {
            $applications[] = array(
                'name'              => $name,
                'current_version'   => $setupXML->version
            );
        }
        
        return $applications;
    }
}
