<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Setup_Update_10 extends Setup_Update_Abstract
{
    const RELEASE010_UPDATE059 = 'release010::update059';

    static protected $_allUpdates = [
        self::PRIO_TB_STRUCT_UPDATE         => [
            self::RELEASE010_UPDATE059          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update059',
            ],
        ],
    ];

    public function update059()
    {
        $counter = 1;
        $processedIds = [];
        do {
            $cont = false;
            $stmt = $this->_db->select()->from(['r1' => SQL_TABLE_PREFIX . 'roles'], ['r1.id', 'r1.name'])
                ->join(['r2' => SQL_TABLE_PREFIX . 'roles'], 'r1.name = r2.name AND r1.id <> r2.id AND
                    (r1.deleted_time IS NULL OR r1.deleted_time = "1970-01-01 00:00:00") AND
                    (r2.deleted_time IS NULL OR r2.deleted_time = "1970-01-01 00:00:00")',
                    ['id2' => 'r2.id'])->limit(1000)->query();
            foreach ($stmt->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                if (isset($processedIds[$row[0]]) || isset($processedIds[$row[2]])) {
                    continue;
                }
                $processedIds[$row[0]] = true;
                $processedIds[$row[2]] = true;
                $cont = true;
                $this->_db->update(SQL_TABLE_PREFIX . 'roles', ['name' => $row[1] . '_' . $counter ],
                    'id = "' . $row[0] . '"');
            }
            $counter += 1;
            $processedIds = [];
        } while ($cont && $counter < 100);

        $this->_db->update(SQL_TABLE_PREFIX . 'roles', ['deleted_time' => '1970-01-01 00:00:00'],
            'deleted_time IS NULL');

        $this->_backend->alterCol('roles', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>deleted_time</name>
                <type>datetime</type>
                <notnull>true</notnull>
                <default>1970-01-01 00:00:00</default>
            </field>'));

        if ($this->getTableVersion('roles') < 5) {
            $this->setTableVersion('roles', 5);
        }

        $this->addApplicationUpdate('Tinebase', '10.59', self::RELEASE010_UPDATE059);
    }
}