<?php declare(strict_types=1);

/**
 * OVpnApi Realm Controller
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * OVpnApi Realm Controller
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_OVpnApiRealm extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Admin_Config::APP_NAME;
        $this->_modelName = Admin_Model_OVpnApiRealm::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => Admin_Model_OVpnApiRealm::class,
            Tinebase_Backend_Sql::TABLE_NAME    => Admin_Model_OVpnApiRealm::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}