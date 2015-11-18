<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     HumanResources
 */
class HumanResources_Setup_Initialize extends Setup_Initialize
{
    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Currently employed employees", // _("Currently employed employees")
            'description'       => "Employees which are currently employed", // _("Employees which are currently employed")
            'filters'           => array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => 1)),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All employees", // _("All employees")
            'description'       => "All available employees", // _("All available employees")
            'filters'           => array(),
        ))));
        
        // Accounts
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_AccountFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All accounts", // _("All accounts")
            'description'       => "All available accounts", // _("All available accounts")
            'filters'           => array(),
        ))));
    }
    
    /**
     * init example workingtime models
     */
    function _initializeWorkingTimeModels()
    {
        $translate = Tinebase_Translation::getTranslation('HumanResources');
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => $translate->_('Full-time 40 hours'),
            'working_hours' => '40',
            'type'  => 'static',
            'json'  => '{"days":[8,8,8,8,8,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => $translate->_('Full-time 37.5 hours'),
            'working_hours' => '37.5',
            'type'  => 'static',
            'json'  => '{"days":[8,8,8,8,5.5,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => $translate->_('Part-time 20 hours'),
            'working_hours' => '20',
            'type'  => 'static',
            'json'  => '{"days":[4,4,4,4,4,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
    }
    

    /**
     * init application folders
     */
    protected function _initializeFolders()
    {
        self::createReportTemplatesFolder();
    }
    
    
    /**
     * create reporting templates folder
     */
    public static function createReportTemplatesFolder()
    {
        $templateContainer = Tinebase_Container::getInstance()->createSystemContainer(
            'HumanResources',
            'Report Templates',
            HumanResources_Config::REPORT_TEMPLATES_CONTAINER_ID
        );
        try {
            Tinebase_FileSystem::getInstance()->createContainerNode($templateContainer);
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create template folder: ' . $teb);
        }
    }
}
