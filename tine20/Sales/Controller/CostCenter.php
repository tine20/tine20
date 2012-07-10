<?php
/**
 * CostCenter controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CostCenter controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_CostCenter extends Tinebase_Controller_Record_Abstract
{

    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     *
     * @var boolean
     */
    protected $_doRightChecks = FALSE;

    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = TRUE;

    /**
     * omit mod log for this records
     *
     * @var boolean
     */
    protected $_omitModLog = TRUE;
    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_CostCenter();
        $this->_modelName = 'Sales_Model_CostCenter';
    }

    /**
     * holds the instance of the singleton
     * @var Sales_Controller_CostCenter
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     * @return Sales_Controller_CostCenter
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller_CostCenter();
        }

        return self::$_instance;
    }
}
