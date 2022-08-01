<?php declare(strict_types=1);

/**
 * OVpnApi Account Controller
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * OVpnApi Account Controller
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_OVpnApiAccount extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = Admin_Model_OVpnApiAccount::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => Admin_Model_OVpnApiAccount::class,
            Tinebase_Backend_Sql::TABLE_NAME    => Admin_Model_OVpnApiAccount::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $user = new Tinebase_Model_FullUser([
            'id' => 'ovpnapi',
            'mfa_configs' => $_record->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS}
        ], true);

        Tinebase_User::getInstance()->treatMFA($user); /** @phpstan-ignore-line */
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $user = new Tinebase_Model_FullUser([
            'id' => 'ovpnapi',
            'mfa_configs' => $_record->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS},
        ], true);
        $oldUser = new Tinebase_Model_FullUser([
            'id' => 'ovpnapi',
            'mfa_configs' => $_oldRecord->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS}
        ], true);

        Tinebase_User::getInstance()->treatMFA($user, $oldUser); /** @phpstan-ignore-line */
    }

    protected function _inspectDelete(array $_ids)
    {
        $_ids = parent::_inspectDelete($_ids);

        $user = new Tinebase_Model_FullUser([
            'id' => 'ovpnapi',
            'mfa_configs' => new Tinebase_Record_RecordSet(Admin_Model_OVpnApi_AuthConfig::class, [])
        ], true);
        foreach ($this->getMultiple($_ids) as $record) {
            $oldUser = new Tinebase_Model_FullUser([
                'id' => 'ovpnapi',
                'mfa_configs' => $record->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS},
            ], true);

            Tinebase_User::getInstance()->treatMFA($user, $oldUser); /** @phpstan-ignore-line */
        }

        return $_ids;
    }
}