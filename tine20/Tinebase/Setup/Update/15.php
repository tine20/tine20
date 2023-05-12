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
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE015_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE015_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE015_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE015_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE015_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE015_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE015_UPDATE012 = __CLASS__ . '::update012';
    const RELEASE015_UPDATE013 = __CLASS__ . '::update013';
    const RELEASE015_UPDATE014 = __CLASS__ . '::update014';
    const RELEASE015_UPDATE015 = __CLASS__ . '::update015';
    const RELEASE015_UPDATE016 = __CLASS__ . '::update016';
    const RELEASE015_UPDATE017 = __CLASS__ . '::update017';
    const RELEASE015_UPDATE018 = __CLASS__ . '::update018';
    const RELEASE015_UPDATE019 = __CLASS__ . '::update019';
    const RELEASE015_UPDATE020 = __CLASS__ . '::update020';
    const RELEASE015_UPDATE021 = __CLASS__ . '::update021';
    const RELEASE015_UPDATE022 = __CLASS__ . '::update022';
    const RELEASE015_UPDATE023 = __CLASS__ . '::update023';
    const RELEASE015_UPDATE024 = __CLASS__ . '::update024';
    const RELEASE015_UPDATE025 = __CLASS__ . '::update025';
    const RELEASE015_UPDATE026 = __CLASS__ . '::update026';
    const RELEASE015_UPDATE027 = __CLASS__ . '::update027';
    const RELEASE015_UPDATE028 = __CLASS__ . '::update028';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE015_UPDATE019          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update019',
            ],
        ],
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
            self::RELEASE015_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE015_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE015_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE015_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE015_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE015_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
            self::RELEASE015_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
            self::RELEASE015_UPDATE013          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update013',
            ],
            self::RELEASE015_UPDATE014          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update014',
            ],
            self::RELEASE015_UPDATE017          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update017',
            ],
            self::RELEASE015_UPDATE020          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update020',
            ],
            self::RELEASE015_UPDATE021          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update021',
            ],
            self::RELEASE015_UPDATE023          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update023',
            ],
            self::RELEASE015_UPDATE025          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update025',
            ],
            self::RELEASE015_UPDATE026          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update026',
            ],
            self::RELEASE015_UPDATE028          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update028',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE          => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE015_UPDATE015          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update015',
            ],
            self::RELEASE015_UPDATE016          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update016',
            ],
            self::RELEASE015_UPDATE018          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update018',
            ],
            self::RELEASE015_UPDATE022          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update022',
            ],
            self::RELEASE015_UPDATE024          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update024',
            ],
            self::RELEASE015_UPDATE027          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update027',
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
        Tinebase_TransactionManager::getInstance()->rollBack();
        if (!$this->_backend->columnExists('skip_upstream_updates', 'importexport_definition')) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>skip_upstream_updates</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>'));
        }
        if ($this->getTableVersion('importexport_definition') < 14) {
            $this->setTableVersion('importexport_definition', 14);
        };

        $this->addApplicationUpdate('Tinebase', '15.4', self::RELEASE015_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_CostCenter::class,
            Tinebase_Model_CostUnit::class,
        ]);

        $pfInit = new Tinebase_Setup_Initialize();
        $pfInit->_initializePF();

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.5', self::RELEASE015_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_MunicipalityKey::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.6', self::RELEASE015_UPDATE006);
    }

    public function update007()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('importexport_definition') < 14) {
            $this->setTableVersion('importexport_definition', 14);
        };
        if ($this->getTableVersion('tags') == 14) {
            $this->setTableVersion('tags', 10);
        };
        if (!$this->_backend->columnExists('filter', 'importexport_definition')) {
            $this->_backend->addCol('importexport_definition', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>filter</name>
                    <type>text</type>
                    <length>16000</length>
                </field>'));
        }
        $this->setTableVersion('importexport_definition', 15);
        $this->addApplicationUpdate('Tinebase', '15.7', self::RELEASE015_UPDATE007);
    }

    public function update008()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.8', self::RELEASE015_UPDATE008);
    }

    public function update009()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_MunicipalityKey::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.9', self::RELEASE015_UPDATE009);
    }
    
    public function update010()
    {
        $tables = [];
        foreach (Tinebase_Application::getInstance()->getApplications() as $app) {
            $tables = array_merge($tables, Tinebase_Application::getInstance()->getApplicationTables($app));
        }

        $models = Tinebase_Application::getInstance()->getModelsOfAllApplications(true);
        asort($models);
        /** @var Tinebase_Record_Interface $model */
        foreach ($models as $model) {
            if (!($mc = $model::getConfiguration()) || !($tableName = $mc->getTableName())) {
                continue;
            }

            if (!in_array($tableName, $tables)) {
                list($app) = explode('_', $model);
                Tinebase_Application::getInstance()->addApplicationTable($app, $tableName, $mc->getVersion() ?: 1);
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.10', self::RELEASE015_UPDATE010);
    }

    public function update011()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_backend->alterCol('config', new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>value</name>
                <type>text</type>
            </field>'));
        if ($this->getTableVersion('config') < 2) {
            $this->setTableVersion('config', 2);
        }
        $this->addApplicationUpdate('Tinebase', '15.11', self::RELEASE015_UPDATE011);
    }

    public function update012()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('groups') < 10) {
            $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['is_deleted' => 0], 'is_deleted IS NULL');
            $this->_backend->alterCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>is_deleted</name>
                    <type>boolean</type>
                    <default>false</default>
                    <notnull>true</notnull>
                </field>'));
            foreach ($this->getDb()->query('SELECT GROUP_CONCAT(id), count(*) as c FROM ' . SQL_TABLE_PREFIX .
                    'groups GROUP BY name HAVING c > 1')->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                $date = new Tinebase_DateTime('1970-01-01 00:00:01');
                foreach (explode(',', $row[0]) as $num => $id) {
                    if (0 === $num) continue;
                    $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['deleted_time' => $date->toString()], $this->getDb()->quoteInto('id = ?', $id));
                    $date->addSecond(1);
                }
            }
            $this->getDb()->update(SQL_TABLE_PREFIX . 'groups', ['deleted_time' => '1970-01-01 00:00:00'], 'deleted_time IS NULL');
            $this->_backend->alterCol('groups', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>deleted_time</name>
                    <type>datetime</type>
                    <notnull>true</notnull>
                    <default>1970-01-01 00:00:00</default>
                </field>'));
            $this->setTableVersion('groups', 10);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.12', self::RELEASE015_UPDATE012);
    }

    public function update013()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        try {
            $this->_backend->dropForeignKey('notes', 'notes::note_type_id--note_types::id');
        } catch (Exception $e) {
            // key might have already been dropped
        }
        $this->_backend->dropTable('note_types');

        $db = Tinebase_Core::getDb();
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE . '" WHERE note_type_id = "1"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_TELEPHONE . '" WHERE note_type_id = "2"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_EMAIL . '" WHERE note_type_id = "3"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED . '" WHERE note_type_id = "4"');
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'notes SET note_type_id = "' . Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED . '" WHERE note_type_id = "5"');

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.13', self::RELEASE015_UPDATE013);
    }

    public function update014()
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');
        $app->order = 0;
        Tinebase_Application::getInstance()->updateApplication($app);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.14', self::RELEASE015_UPDATE014);
    }

    public function update015()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = Tinebase_Core::getDb();

        $rows = $db->query('SELECT * FROM ' . SQL_TABLE_PREFIX .
            'relations WHERE rel_id like "ext-gen%" and is_deleted=0')->fetchAll();
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Fixing broken relations: ' . count($rows));
        $rowsbyRelId = [];
        foreach ($rows as $row) {
            $rowsbyRelId[$row['rel_id']][] = $row;
        }

        foreach ($rowsbyRelId as $relId => $relations) {
//            echo '---------------------------------------' . "\n";
//            echo 'rel_id: ' . $relId . "\n";
//            echo 'number of relations: ' . count($relations). "\n";
            if (count($relations) === 2) {
                // just replace rel_ids with new UUID
                $query = 'UPDATE ' . SQL_TABLE_PREFIX . 'relations SET rel_id = "'
                    . Tinebase_Record_Abstract::generateUID() . '",last_modified_time=NOW() WHERE rel_id = "' . $relId . '";';
                // echo "$query \n";
                $db->query($query);
            } else {
                $updatedIds = [];
                foreach ($relations as $relation) {
                    if (! in_array($relation['related_id'], $updatedIds) && ! in_array($relation['own_id'], $updatedIds)) {
                        $query = 'UPDATE ' . SQL_TABLE_PREFIX . 'relations SET rel_id = "'
                            . Tinebase_Record_Abstract::generateUID() . '",last_modified_time=NOW() WHERE rel_id = "'
                            . $relId . '" AND (related_id = "' . $relation['related_id']. '" OR related_id = "' . $relation['own_id']. '");';
                        // echo "$query \n";
                        $db->query($query);
                        $updatedIds[] = $relation['own_id'];
                        $updatedIds[] = $relation['related_id'];
                    }
                }
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.15', self::RELEASE015_UPDATE015);
    }

    public function update016()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $scheduler = Tinebase_Scheduler::getInstance();
        if ($scheduler->hasTask('Tinebase_Controller_ScheduledImport')) {
            $scheduler->removeTask('Tinebase_Controller_ScheduledImport');
        }

        if ($this->_backend->tableExists('import')) {
            $this->_backend->dropTable('import');
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.16', self::RELEASE015_UPDATE016);
    }

    public function update017()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_SchedulerTask::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.17', self::RELEASE015_UPDATE017);
    }

    public function update018()
    {
        // remove import table from app tables (this might not have happened in 15.16)
        Tinebase_Application::getInstance()->removeApplicationTable('Tinebase', 'import');

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.18', self::RELEASE015_UPDATE018);
    }

    public function update019()
    {
        $columns = [
            Tinebase_Model_MunicipalityKey::TABLE_NAME => [
                Tinebase_Model_MunicipalityKey::FLD_GEBIETSSTAND,
                Tinebase_Model_MunicipalityKey::FLD_BEVOELKERUNGSSTAND,
            ],
        ];

        if (Tinebase_Application::getInstance()->isInstalled('EFile')) {
            $columns[EFile_Model_FileMetadata::TABLE_NAME] = [
                EFile_Model_FileMetadata::FLD_DURATION_START,
                EFile_Model_FileMetadata::FLD_DURATION_END,
                EFile_Model_FileMetadata::FLD_FINAL_DECREE_DATE,
                EFile_Model_FileMetadata::FLD_RETENTION_PERIOD_END_DATE,
                EFile_Model_FileMetadata::FLD_DISPOSAL_DATE,
            ];
        }
        if (Tinebase_Application::getInstance()->isInstalled('GDPR')) {
            $columns['gdpr_dataintendedpurposerecords'] = [
                'agreeDate',
                'withdrawDate'
            ];
            $columns[Addressbook_Model_Contact::TABLE_NAME] = [
                GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_EXPIRY_CUSTOM_FIELD_NAME,
            ];
        }
        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            $columns['humanresources_contract'] = [
                'start_date',
                'end_date',
            ];
            $columns['humanresources_costcenter'] = [
                'start_date',
            ];
            /* we don't need this one, we just truncate the time value
             *$columns['humanresources_wt_dailyreport'] = [
                'date',
            ];*/
            $columns['humanresources_employee'] = [
                'bday',
                'employment_begin',
                'employment_end',
            ];
            $columns[HumanResources_Model_FreeTime::TABLE_NAME] = [
                'firstday_date',
                'lastday_date',
            ];
            $columns[HumanResources_Model_StreamModality::TABLE_NAME] = [
                HumanResources_Model_StreamModality::FLD_START,
                HumanResources_Model_StreamModality::FLD_END,
                HumanResources_Model_StreamModality::FLD_TRACKING_START,
                HumanResources_Model_StreamModality::FLD_TRACKING_END,
            ];
            $columns[HumanResources_Model_StreamModalReport::TABLE_NAME] = [
                HumanResources_Model_StreamModalReport::FLD_START,
                HumanResources_Model_StreamModalReport::FLD_END,
            ];
        }
        if (Tinebase_Application::getInstance()->isInstalled('Projects')) {
            $columns[Projects_Model_Project::TABLE_NAME] = [
                Projects_Model_Project::FLD_START,
                Projects_Model_Project::FLD_END,
            ];
        }
        if (Tinebase_Application::getInstance()->isInstalled('Sales')) {
            $columns[Sales_Model_Boilerplate::TABLE_NAME] = [
                Sales_Model_Boilerplate::FLD_FROM,
                Sales_Model_Boilerplate::FLD_UNTIL,
            ];
            $columns[Sales_Model_Document_Invoice::TABLE_NAME] = [
                Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE,
            ];
            $columns[Sales_Model_Document_Delivery::TABLE_NAME] = [
                Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE,
            ];
            $columns[Sales_Model_Document_Offer::TABLE_NAME] = [
                Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE,
            ];
            $columns[Sales_Model_Document_Order::TABLE_NAME] = [
                Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE,
            ];
            $columns['sales_contracts'] = [
                'start_date',
                'end_date',
            ];
            $columns['sales_sales_invoices'] = [
                'start_date',
                'end_date',
            ];
            $columns['sales_product_agg'] = [
                'start_date',
                'end_date',
                'last_autobill',
            ];
            $columns['sales_purchase_invoices'] = [
                'payed_at',
                'dunned_at',
            ];
        }

        $db = $this->getDb();
        foreach ($columns as $table => $cols) {
            if (!$this->_backend->tableExists($table)) {
                continue;
            }
            $query = 'UPDATE ' . SQL_TABLE_PREFIX . $table . ' SET ';
            $select = 'SELECT id';
            $first = true;
            foreach ($cols as $col) {
                if (!$this->_backend->columnExists($col, $table)) {
                    continue;
                }
                $query .= (!$first ? ', ' : '') . $db->quoteIdentifier($col) . ' = ?';
                $select .= ', ' . $db->quoteIdentifier($col);
                $first = false;
            }
            if ($first) {
                continue;
            }
            $query .= ' WHERE id = ?';
            $select .=  ' FROM ' . SQL_TABLE_PREFIX . $table;
            foreach ($db->query($select)->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                $q = $query;
                for ($i = 1; $i < count($row); ++$i) {
                    $col = $row[$i];
                    if ($col) {
                        try {
                            $col = (new Tinebase_DateTime($col, 'UTC'))->setTimezone('CET')->toString();
                        } catch (Exception $e) {
                            Tinebase_Exception::log($e);
                            $col = new Zend_Db_Expr('NULL');
                        }
                    } else {
                        $col = new Zend_Db_Expr('NULL');

                    }
                    $q = $db->quoteInto($q, $col, null, 1);
                }
                $db->query($db->quoteInto($q, $row[0], null, 1));
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.19', self::RELEASE015_UPDATE019);
    }

    public function update020()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $classes = [
            Tinebase_Model_MunicipalityKey::class,
        ];
        if (Tinebase_Application::getInstance()->isInstalled('EFile')) {
            $classes[] = EFile_Model_FileMetadata::class;
        }
        if (Tinebase_Application::getInstance()->isInstalled('GDPR')) {
            $classes[] = GDPR_Model_DataIntendedPurposeRecord::class;
            $classes[] = Addressbook_Model_Contact::class;
        }
        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            $classes[] = HumanResources_Model_Contract::class;
            $classes[] = HumanResources_Model_CostCenter::class;
            $classes[] = HumanResources_Model_DailyWTReport::class;
            $classes[] = HumanResources_Model_Employee::class;
            $classes[] = HumanResources_Model_FreeTime::class;
            $classes[] = HumanResources_Model_StreamModality::class;
            $classes[] = HumanResources_Model_StreamModalReport::class;
        }
        if (Tinebase_Application::getInstance()->isInstalled('Projects')) {
            $classes[] = Projects_Model_Project::class;
        }
        if (Tinebase_Application::getInstance()->isInstalled('Sales')) {
            $classes[] = Sales_Model_Boilerplate::class;
            $classes[] = Sales_Model_Document_Invoice::class;
            $classes[] = Sales_Model_Document_Delivery::class;
            $classes[] = Sales_Model_Document_Offer::class;
            $classes[] = Sales_Model_Document_Order::class;
            $query = $this->_backend->addAlterCol('', 'sales_contracts', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>start_date</name>
                    <type>date</type>
                </field>'));
            $this->getDb()->query($this->_backend->addAlterCol($query, 'sales_contracts', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>end_date</name>
                    <type>date</type>
                </field>')));
            if ($this->getTableVersion('sales_contracts') < 11) {
                $this->setTableVersion('sales_contracts', 11);
            }
            if ($this->_backend->tableExists('sales_sales_invoices')) {
                $query = $this->_backend->addAlterCol('', 'sales_sales_invoices', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>start_date</name>
                    <type>date</type>
                </field>'));
                $this->getDb()->query($this->_backend->addAlterCol($query, 'sales_sales_invoices', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>end_date</name>
                    <type>date</type>
                </field>')));
                if ($this->getTableVersion('sales_sales_invoices') < 8) {
                    $this->setTableVersion('sales_sales_invoices', 8);
                }
            }
            if ($this->_backend->tableExists('sales_product_agg')) {
                $query = $this->_backend->addAlterCol('', 'sales_product_agg', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>start_date</name>
                    <type>date</type>
                </field>'));
                $this->getDb()->query($this->_backend->addAlterCol($query, 'sales_product_agg', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>end_date</name>
                    <type>date</type>
                </field>')));
                $this->getDb()->query($this->_backend->addAlterCol($query, 'sales_product_agg', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>last_autobill</name>
                    <type>date</type>
                    <notnull>false</notnull>
                    <default>null</default>
                </field>')));
                if ($this->getTableVersion('sales_product_agg') < 6) {
                    $this->setTableVersion('sales_product_agg', 6);
                }
            }
            $query = $this->_backend->addAlterCol('', 'sales_purchase_invoices', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>payed_at</name>
                    <type>date</type>
                </field>'));
            $this->getDb()->query($this->_backend->addAlterCol($query, 'sales_purchase_invoices', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>dunned_at</name>
                    <type>date</type>
                </field>')));
            if ($this->getTableVersion('sales_purchase_invoices') < 6) {
                $this->setTableVersion('sales_purchase_invoices', 6);
            }
        }
        Setup_SchemaTool::updateSchema(array_unique($classes));

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.20', self::RELEASE015_UPDATE020);
    }

    public function update021()
    {
        if(!Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY}) {
            Tinebase_Auth_CredentialCache_Adapter_Shared::setRandomKeyInConfig();
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.21', self::RELEASE015_UPDATE021);
    }

    public function update022()
    {
        Tinebase_Application::getInstance()->removeApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName('Tinebase'),
            'note_types');
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.22', self::RELEASE015_UPDATE022);
    }

    public function update023()
    {
        if (! $this->_backend->tableExists(Tinebase_Model_ActionLog::TABLE_NAME)) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Setup_SchemaTool::updateSchema([Tinebase_Model_ActionLog::class]);
            Tinebase_Application::getInstance()->addApplicationTable('Tinebase',
                Tinebase_Model_ActionLog::TABLE_NAME);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.23', self::RELEASE015_UPDATE023);
    }

    public function update024()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_backend->truncateTable(Tinebase_Model_Path::TABLE_NAME);

        Tinebase_Controller::getInstance()->rebuildPaths();
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.24', self::RELEASE015_UPDATE024);
    }

    public function update025()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('importexport_definition') < 16) {
            $this->_backend->alterCol('importexport_definition', new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>filename</name>
                    <type>text</type>
                    <length>255</length>
                </field>'));
            $this->setTableVersion('importexport_definition', 16);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.25', self::RELEASE015_UPDATE025);
    }

    public function update026()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $found = false;
        foreach ($this->_backend->getExistingSchema('tree_filerevisions')->indices as $index) {
            if (['hash'] === $index->field) {
                $found = true;
            }
        }
        if (!$found) {
            $this->_backend->addIndex('tree_filerevisions', new Setup_Backend_Schema_Index_Xml('<index>
                    <name>hash</name>
                    <field>
                        <name>hash</name>
                    </field>
                </index>'));
        }
        if ($this->getTableVersion('tree_filerevisions') < 5) {
            $this->setTableVersion('tree_filerevisions', 5);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.26', self::RELEASE015_UPDATE026);
    }

    public function update027()
    {
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'tree_fileobjects SET creation_time = NOW() WHERE creation_time IS NULL');
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'tree_fileobjects SET created_by = "' .
            Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_REPLICATION)->getId() . '" WHERE created_by IS NULL');
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.27', self::RELEASE015_UPDATE027);
    }

    public function update028()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_BankHoliday::class,
            Tinebase_Model_BankHolidayCalendar::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '15.28', self::RELEASE015_UPDATE028);
    }
}
