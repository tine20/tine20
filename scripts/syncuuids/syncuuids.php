<?php
/**
 * - get users + groups from ldap tree
 * - create mapping
 * - search/replace all ids in sql dump
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

// set include path to find all needed classes
set_include_path(
    './lib'
    . PATH_SEPARATOR . '/usr/share/tine20'
    . PATH_SEPARATOR . '/usr/share/tine20/library'
    . PATH_SEPARATOR . '/usr/share/tine20/vendor/zendframework/zendframework1/library'
    . PATH_SEPARATOR . '/etc/tine20'
    . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$sync = new Sync();
$sync->doSync();
