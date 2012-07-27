<?php

$paths = array(
realpath(dirname(__FILE__)),
realpath(dirname(__FILE__) . '/../lib'),
get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

function getTestDatabase()
{
    if (file_exists('/tmp/Syncroton_test.sq3')) {
        unlink('/tmp/Syncroton_test.sq3');
    }
    
    $sql = file_get_contents(dirname(__FILE__) . '/../docs/syncroton.sql');

    $sql = explode(';', $sql);
    
    // create in memory database by default 
    $params = array (
        #'dbname' => '/tmp/Syncroton_test.sq3',
        'dbname' => ':memory:'
    );
    
    $db = Zend_Db::factory('PDO_SQLITE', $params);
    
    // enable foreign keys
    #$db->query('PRAGMA read_uncommitted = true');
    
    foreach ($sql as $sql_query) {
        if (strlen($sql_query) > 10) {
            // Convert mysql DDL to SQLite format
            $start = strpos($sql_query, '(');
            $end   = strrpos($sql_query, ')');
            $cols  = substr($sql_query, $start, $end - $start);
            $cols  = explode(',', $cols);

            foreach ($cols as $idx => $col) {
                if (preg_match('/^KEY /', ltrim($col))) {
                    unset($cols[$idx]);
                    continue;
                }

                if (preg_match('/^CONSTRAINT /', ltrim($col))) {
                    unset($cols[$idx]);
                    continue;
                }

                $col = preg_replace('/UNIQUE KEY `[^`]+`/', 'UNIQUE', $col);
                $col = preg_replace('/`\([0-9]+\)/', '`', $col);

                $cols[$idx] = $col;
            }

            $sql_query = substr($sql_query, 0, $start) . "\n" . implode($cols, ',') . ")";

            $db->query($sql_query);
        }
    }
    
    return $db;
}
