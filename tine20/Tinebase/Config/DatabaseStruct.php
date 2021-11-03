<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 */

/**
 * Zend_Config like access to array data
 *
 * @package     Tinebase
 * @subpackage  Config
 */
class Tinebase_Config_DatabaseStruct extends Tinebase_Config_Struct
{
    const ADAPTER = 'adapter';
    const DBNAME = 'dbname';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const HOST = 'host';
    const PORT = 'port';
    
    public function __construct($data = array())
    {
        parent::__construct($data);

        $this->_struct = [
            self::ADAPTER => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
                Tinebase_Config_Abstract::DEFAULT_STR => 'pdo_mysql',
            ],
            self::DBNAME => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
                Tinebase_Config_Abstract::DEFAULT_STR => '',
            ],
            self::USERNAME => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
                Tinebase_Config_Abstract::DEFAULT_STR => '',
            ],
            self::PASSWORD => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
            ],
            self::HOST => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_STRING,
                Tinebase_Config_Abstract::DEFAULT_STR => 'localhost',
            ],
            self::PORT => [
                Tinebase_Config_Abstract::TYPE => Tinebase_Config_Abstract::TYPE_INT,
                Tinebase_Config_Abstract::DEFAULT_STR => 3306,
            ]
        ];
    }
}
