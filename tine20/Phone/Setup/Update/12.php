<?php

/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Phone_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE012_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],

        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE012_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update001()
    {
        if (!$this->_backend->columnExists('resolved_destination', 'phone_callhistory')) {
            $this->_backend->addCol('phone_callhistory', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>resolved_destination</name>
                    <type>text</type>
                    <length>64</length>
                </field>'));
        }
        if ($this->getTableVersion('phone_callhistory') < 4) {
            $this->setTableVersion('phone_callhistory', 4);
        }
        $this->addApplicationUpdate('Phone', '12.1', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        // needs to be reexecuted, so moved to update003
        $this->addApplicationUpdate('Phone', '12.2', self::RELEASE012_UPDATE002);
    }

    public function update003()
    {
        $call = Phone_Controller_Call::getInstance();
        $result = $this->_db->select()->from(SQL_TABLE_PREFIX . 'phone_callhistory', ['id', 'destination'])
            ->query(Zend_Db::FETCH_NUM);
        foreach ($result->fetchAll() as $row) {
            $this->_db->update(SQL_TABLE_PREFIX . 'phone_callhistory', [
                'resolved_destination' =>
                    Addressbook_Model_Contact::normalizeTelephoneNum($call->resolveInternalNumber($row[1]))
            ], 'id = ' . $this->_db->quote($row[0]));
        }

        $this->addApplicationUpdate('Phone', '12.3', self::RELEASE012_UPDATE003);
    }
}
