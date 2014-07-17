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
    protected $_duplicateCheckFields = array(array('number'));

    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by doRightChecks
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
    protected $_purgeRecords = FALSE;

    /**
     * omit mod log for this records
     *
     * @var boolean
     */
    protected $_omitModLog = FALSE;
    
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
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_duplicateCheck($_record);
    }
    
    
    /**
     * the singleton pattern
     * @return Sales_Controller_CostCenter
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
