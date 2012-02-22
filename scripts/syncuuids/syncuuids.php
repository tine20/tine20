<?php
/**
 * - get users + groups from ldap tree
 * - create mapping
 * - search/replace all ids in sql dump
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo    implement
 * @todo    use Zend_Ldap to fetch users/groups
 * @todo    allow to set filename as param
 * @todo    get LDAP bind data from config file
 * @todo    get users/groups to create mapping
 * @todo    preg_replace occurrences in file
 */

// set include path to find all needed classes
set_include_path('./lib' . PATH_SEPARATOR . '/var/www/tine20' . PATH_SEPARATOR . '/var/www/tine20/library' .   PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$sync = new Sync();
$sync->doSync();

