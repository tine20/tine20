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
    public function installApplications($applicationNames)
    {
        $decodedNames = Zend_Json::decode($applicationNames);
        $this->_controller->installApplications($decodedNames);

        if(in_array('Tinebase', $decodedNames)) {
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
    public function updateApplications($applicationNames)
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
    public function uninstallApplications($applicationNames)
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
    public function searchApplications()
    {
        // get installable apps
        $installable = $this->_controller->getInstallableApplications();
        
        // get installed apps
        $installed = Tinebase_Application::getInstance()->getApplications(NULL, 'id')->toArray();
        
        // merge to create result array
        $applications = array();
        foreach ($installed as $application) {
            $application['current_version'] = (string) $installable[$application['name']]->version;
            $applications[] = $application;
            unset($installable[$application['name']]);
        }
        foreach ($installable as $name => $setupXML) {
            $applications[] = array(
                'name'              => $name,
                'current_version'   => (string) $setupXML->version
            );
        }
        
        return array(
            'results'       => $applications,
            'totalcount'    => count($applications)
        );
    }
    
    /**
     * Returns registry data of tinebase.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $locale = Tinebase_Core::get('locale');
        
        $registryData =  array(
            'timeZone'         => Tinebase_Core::get('userTimeZone'),
            'locale'           => array(
                'locale'   => $locale->toString(), 
                'language' => $locale->getLanguageTranslation($locale->getLanguage()),
                'region'   => $locale->getCountryTranslation($locale->getRegion()),
            ),
        );
        
        return $registryData;
    }
    
    /**
     * Returns registry data of all applications current user has access to
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getAllRegistryData()
    {
        $registryData['Tinebase'] = $this->getRegistryData();
        
        die(Zend_Json::encode($registryData));
    }
}
