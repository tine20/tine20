<?php
/**
 * Tine 2.0
 *
 * @package     Bookmarks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Bookmarks_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Bookmarks_Model_Bookmark::class]);
        $this->addApplicationUpdate(Bookmarks_Config::APP_NAME, '1.1', self::RELEASE001_UPDATE001);
    }
}
