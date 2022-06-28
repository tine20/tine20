<?php
/**
 * History controller for OnlyOfficeIntegrator application
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * History controller class for OnlyOfficeIntegrator application
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Controller
 */
class OnlyOfficeIntegrator_Controller_History extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var OnlyOfficeIntegrator_Controller_History
     */
    private static $_instance = null;

    /**
     * OnlyOfficeIntegrator_Controller_History constructor.
     * @throws \Tinebase_Exception_Backend_Database
     */
    protected function __construct()
    {
        $this->_applicationName = OnlyOfficeIntegrator_Config::APP_NAME;
        $this->_modelName = OnlyOfficeIntegrator_Model_History::class;

        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => OnlyOfficeIntegrator_Model_History::class,
            Tinebase_Backend_Sql::TABLE_NAME    => OnlyOfficeIntegrator_Model_History::TABLE_NAME,
            //defaults to Tinebase_Backend_Sql::MODLOG_ACTIVE => false,
        ]);
        $this->_handleDependentRecords = false; // its a tiny performance enhancement (at time of writing)
        $this->_doContainerACLChecks = false;
        $this->_doRightChecks = false;
        //defaults to $this->_purgeRecords = true;
    }

    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return OnlyOfficeIntegrator_Controller_History
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function getPreviousVersion($nodeId, $revision)
    {
        $version = self::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            OnlyOfficeIntegrator_Model_History::class, [
                ['field' => OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID, 'operator' => 'equals', 'value' => $nodeId],
                ['field' => OnlyOfficeIntegrator_Model_History::FLDS_NODE_REVISION, 'operator' => 'equals', 'value' => $revision],
        ]), null, false, [OnlyOfficeIntegrator_Model_History::FLDS_VERSION]);

        if (empty($version)) {
            return 0;
        }
        return (int)key($version);
    }
}
