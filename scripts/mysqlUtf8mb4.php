<?php
/**
 * Tine 2.0
 *
 * @package     scripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * script to migrate mysql to utf8mb4
 */

$autoloader = require __DIR__ . '/../tine20/vendor/autoload.php';

$config = require 'config.inc.php';
$dbConfigArray = $config['database'];

if (! defined('SQL_TABLE_PREFIX')) {
    define('SQL_TABLE_PREFIX', isset($dbConfigArray['tableprefix']) ? $dbConfigArray['tableprefix'] : 'tine20_');
}

foreach (array('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY', 'PDO::MYSQL_ATTR_INIT_COMMAND') as $pdoConstant) {
    if (! defined($pdoConstant)) {
        throw new Tinebase_Exception_Backend_Database($pdoConstant . ' is not defined. Please check PDO extension.');
    }
}

$dbConfigArray['adapter'] = 'Pdo_Mysql';
$dbConfigArray['adapterNamespace'] = 'Tinebase_Backend_Sql_Adapter';
$dbConfigArray['charset'] = 'utf8mb4';

// force some driver options
$dbConfigArray['driver_options'] = array(
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => FALSE,
);
$dbConfigArray['options']['init_commands'] = array(
    "SET time_zone = '+0:00'",
    "SET SQL_MODE = 'STRICT_ALL_TABLES'",
    "SET SESSION group_concat_max_len = 4294967295"
);
/** @var Zend_Db_Adapter_Pdo_Mysql $db */
$db = Zend_Db::factory('Pdo_Mysql', $dbConfigArray);


if (($ilp = $db->query('SELECT @@innodb_large_prefix')->fetchColumn()) !== '1') {
    throw new Tinebase_Exception_Backend_Database('innodb_large_prefix seems not be turned on: ' . $ilp);
}
if (($iff = $db->query('SELECT @@innodb_file_format')->fetchColumn()) !== 'Barracuda') {
    throw new Tinebase_Exception_Backend_Database('innodb_file_format seems not to be Barracuda: ' . $iff);
}
if (($ift = $db->query('SELECT @@innodb_file_per_table')->fetchColumn()) !== '1') {
    throw new Tinebase_Exception_Backend_Database('innodb_file_per_table seems not to be turned on: ' . $ift);
}

try {
    $db->query('ALTER DATABASE ' . $db->quoteIdentifier($db->getConfig()['dbname']) .
        ' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci');
} catch (Zend_Db_Exception $zde) {
    echo $zde->getMessage() . PHP_EOL;
}

$tables = $db->listTables();
$tables = array_filter($tables, function ($val) {
    if (strpos($val, SQL_TABLE_PREFIX) === 0) {
        return true;
    }
    return false;
});

$lock = null;
foreach ($tables as $table) {
    $lock .= ($lock === null ? 'LOCK TABLES ' : ', ') . $table . ' WRITE';
}
$db->query($lock);

$db->query('SET foreign_key_checks = 0');
$db->query('SET unique_checks = 0');
foreach ($tables as $table) {
        echo $table . '...' . PHP_EOL; flush();
        $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) . ' ROW_FORMAT = DYNAMIC');
        $db->query('ALTER TABLE ' . $db->quoteIdentifier($table) .
            ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}

$db->query('ALTER TABLE ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'tree_nodes') . ' CHANGE COLUMN ' .
    $db->quoteIdentifier('name') . ' ' . $db->quoteIdentifier('name') .
    ' VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');

$db->query('SET foreign_key_checks = 1');
$db->query('SET unique_checks = 1');

foreach ($tables as $table) {
    $db->query('OPTIMIZE TABLE ' . $db->quoteIdentifier($table));
}

echo PHP_EOL . 'done' . PHP_EOL;