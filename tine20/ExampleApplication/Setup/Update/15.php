<?php

/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class ExampleApplication_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE       => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('ExampleApplication', '15.0', self::RELEASE015_UPDATE000);
    }
    
    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        $foreignKeys = $this->_backend->getExistingForeignKeys('example_application_record');
        foreach ($foreignKeys as $foreignKey) {
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                " Drop index: " . $foreignKey['table_name'] . ' => ' . $foreignKey['constraint_name']);
            $this->_backend->dropForeignKey($foreignKey['table_name'], $foreignKey['constraint_name']);
        }
        
        $db = Tinebase_Core::getDb();
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'example_onetoone SET deleted_time = "1970-01-01 00:00:00" WHERE deleted_time IS NULL');
        
        Setup_SchemaTool::updateSchema([ExampleApplication_Model_OneToOne::class]);
        $this->addApplicationUpdate('ExampleApplication', '15.1', self::RELEASE015_UPDATE001);
    }
}
