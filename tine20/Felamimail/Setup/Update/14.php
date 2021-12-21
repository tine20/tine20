<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class Felamimail_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE014_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE014_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE014_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE=> [
            self::RELEASE014_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE014_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE014_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE   => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        // remove obsolete Felamimail_Model_Message containers that have been created by accident (see \ActiveSync_Frontend_Abstract::createFolder)
        $this->_db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'container WHERE model = "Felamimail_Model_Message"');
        $this->addApplicationUpdate('Felamimail', '14.1', self::RELEASE014_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([Felamimail_Model_AttachmentCache::class]);

        $this->addApplicationUpdate('Felamimail', '14.2', self::RELEASE014_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([Felamimail_Model_Account::class]);

        $this->addApplicationUpdate('Felamimail', '14.3', self::RELEASE014_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([Felamimail_Model_Account::class]);

        $this->addApplicationUpdate('Felamimail', '14.4', self::RELEASE014_UPDATE004);
    }
}
