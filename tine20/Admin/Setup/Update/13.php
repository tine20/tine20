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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
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
}
