<?php
/**
 * CostCenter controller for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CostCenter controller class for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_CostCenter extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => Tinebase_Model_CostCenter::class,
            Tinebase_Backend_Sql::TABLE_NAME    => Tinebase_Model_CostCenter::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Tinebase_Model_CostCenter::class;
        $this->_purgeRecords = false;
        $this->_duplicateCheckOnUpdate = true;
        $this->_doRightChecks = false;
        $this->_doContainerACLChecks = false;
        $this->_duplicateCheckFields = [[Tinebase_Model_CostCenter::FLD_NUMBER]];
    }

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('created_by', 'last_modified_by')
    );
}
