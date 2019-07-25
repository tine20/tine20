<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Timetracker_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $this->updateSchema('Timetracker', array('Timetracker_Model_Timesheet', 'Timetracker_Model_Timeaccount'));
        Tinebase_Db_Table::clearTableDescriptionInCache('timetracker_timesheet');

        $tsBackend = new Timetracker_Backend_Timesheet();
        $data = [
            'accounting_time' => new Zend_Db_Expr(Tinebase_Core::getDb()->quoteIdentifier('duration'))
        ];
        Tinebase_Core::getDb()->update($tsBackend->getTablePrefix() . $tsBackend->getTableName(), $data);

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '13.0', self::RELEASE013_UPDATE001);
    }
}
