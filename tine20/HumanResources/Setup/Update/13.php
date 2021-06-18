<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class HumanResources_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        try {
            $this->addApplicationUpdate('HumanResources', '13.0', self::RELEASE013_UPDATE001);
        } catch (Setup_Exception $se) {
            // ... version was already increased to 13.0 in 12.php ...
        }
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_DailyWTReport::class,
        ]);
        
        $this->addApplicationUpdate('HumanResources', '13.1', self::RELEASE013_UPDATE002);
    }
}
