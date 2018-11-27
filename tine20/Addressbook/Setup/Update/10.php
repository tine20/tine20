<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Addressbook_Setup_Update_10 extends Setup_Update_Abstract
{
    const RELEASE010_UPDATE006 = 'release010::update006';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE010_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];


    public function update006()
    {
        if ($this->getTableVersion('addressbook_lists') == 25) {
            $this->setTableVersion('addressbook_lists', 6);
        }
        $this->addApplicationUpdate('Addressbook', '10.7', self::RELEASE010_UPDATE006);
    }
}