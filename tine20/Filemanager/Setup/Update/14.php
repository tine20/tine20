<?php

/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class Filemanager_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE014_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Filemanager', '14.0', self::RELEASE014_UPDATE000);
    }

    public function update001()
    {
        Tinebase_FileSystem::getInstance()->repairSharedAclOfNode();
        $this->addApplicationUpdate('Filemanager', '14.1', self::RELEASE014_UPDATE001);
    }
}
