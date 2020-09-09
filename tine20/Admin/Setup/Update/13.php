<?php

/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */
class Admin_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $configBackend = Admin_Controller_Config::getInstance()->getBackend();
        foreach ($configBackend->getAll() as $configEntry) {
            $uncertainDecoded = Tinebase_Config::uncertainJsonDecode($configEntry->value);
            if ($uncertainDecoded !== $configEntry->value && !is_array($uncertainDecoded)) {
                $configEntry->value = $uncertainDecoded;
                $configBackend->update($configEntry);
            }
        }
        $this->addApplicationUpdate('Admin', '13.0', self::RELEASE013_UPDATE000);
    }

    /**
     * add missing Felamimail system accounts for current users
     */
    public function update001()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
            ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT} && Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            /* @var Tinebase_Model_FullUser $user */
            foreach (Admin_Controller_User::getInstance()->searchFullUsers('') as $user) {
                if ($user->accountStatus !== Tinebase_Model_User::ACCOUNT_STATUS_ENABLED
                    || $user->visibility !== Tinebase_Model_User::VISIBILITY_DISPLAYED
                ) {
                    // skip invisible and disabled users
                    continue;
                }
                $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($user);
                if (! $account) {
                    Felamimail_Controller_Account::getInstance()->createSystemAccount($user, null);
                }
            }
        }
        $this->addApplicationUpdate('Admin', '13.1', self::RELEASE013_UPDATE001);
    }
}
