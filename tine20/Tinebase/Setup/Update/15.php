<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Tinebase_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            // as we do a raw query, we dont want that table to be changed before we do our query => prio struct
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE          => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],

        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Tinebase', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_Tree_FileObject::class]);
        $this->addApplicationUpdate('Tinebase', '15.1', self::RELEASE015_UPDATE001);
    }

    // as we do a raw query, we dont want that table to be changed before we do our query => prio struct
    public function update002()
    {
        $db = $this->getDb();
        foreach ($db->query('SELECT role_id, application_id from ' . SQL_TABLE_PREFIX . 'role_rights WHERE `right` = "'
                . Tinebase_Acl_Rights_Abstract::RUN . '"')->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            $db->query($db->quoteInto('INSERT INTO ' . SQL_TABLE_PREFIX . 'role_rights SET role_id = ?', $row[0]) .
                $db->quoteInto(', application_id = ?', $row[1]) .
                ', `right` = "' . Tinebase_Acl_Rights_Abstract::MAINSCREEN . '", `id` = "' .
                Tinebase_Record_Abstract::generateUID() . '"');
        }
        $this->addApplicationUpdate('Tinebase', '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([Tinebase_Model_MunicipalityKey::class]);
        $this->addApplicationUpdate('Tinebase', '15.3', self::RELEASE015_UPDATE003);
    }

    public function update004()
    {
        if ($this->getTableVersion('importexport_definition') < 14) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>skip_upstream_updates</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>'));
            $this->setTableVersion('tags', 14);
        };

        $this->addApplicationUpdate('Tinebase', '15.4', self::RELEASE015_UPDATE004);
    }
}
