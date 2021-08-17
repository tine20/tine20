<?php
/**
 * Tine 2.0
 *
 * @package     SimpleFAQ
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class SimpleFAQ_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $this->addApplicationUpdate('SimpleFAQ', '13.0', self::RELEASE013_UPDATE001);
    }
}
