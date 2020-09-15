<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for AuthToken
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_AuthToken extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected $_purgedOldRecordsThisLifecycle = false;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_AuthToken::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_AuthToken::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_AuthToken::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
        $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
     */
    protected function _checkRight($_action)
    {
        if (!$this->_purgedOldRecordsThisLifecycle) {
            $this->_purgedOldRecordsThisLifecycle = true;
            $this->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                ['field' => Tinebase_Model_AuthToken::FLD_VALID_UNTIL, 'operator' => 'before', 'value' => Tinebase_DateTime::now()]
            ]));
        }
        parent::_checkRight($_action);
    }
}
