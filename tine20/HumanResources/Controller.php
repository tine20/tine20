<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * HumanResources Controller
 *
 * @package HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller
     */
    private static $_instance = NULL;

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'HumanResources_Model_Employee';

    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller;
        }

        return self::$_instance;
    }

    /**
     * save HR settings
     *
     * @param array config
     * @return Sales_Model_Config
     *
     * @todo generalize this
     */
    public function setConfig($config)
    {
        if (! Tinebase_Core::getUser()->hasRight('HumanResources', 'admin')) {
            throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on HumanResources'));
        }
        
        foreach(array(HumanResources_Config::VACATION_EXPIRES, HumanResources_Config::DEFAULT_FEAST_CALENDAR) as $cfg) {
            if (! empty($config[$cfg])) {
                HumanResources_Config::getInstance()->set($cfg, $config[$cfg]);
            } else {
                HumanResources_Config::getInstance()->delete($cfg);
            }
        }

        return array('SUCCESS' => TRUE);
    }


    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();

        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        if (HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
        ) {
            $result->addRecord(new CoreData_Model_CoreData(array(
                'id' => 'hr_wagetype',
                'application_id' => $application,
                'model' => HumanResources_Model_WageType::class,
                'label' => 'Wage types' // _('Wage types')
            )));

            $result->addRecord(new CoreData_Model_CoreData(array(
                'id' => 'hr_freetimetype',
                'application_id' => $application,
                'model' => HumanResources_Model_FreeTimeType::class,
                'label' => 'Free time types' // _('Free time types')
            )));
        }

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => 'hr_wts',
            'application_id' => $application,
            'model' => 'HumanResources_Model_WorkingTimeScheme',
            'label' => 'Working time scheme' // _('Working time scheme')
        )));

        return $result;
    }
}
