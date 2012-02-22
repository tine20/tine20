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
    if (file_exists('/tmp/syncope_test.sq3')) {
        unlink('/tmp/syncope_test.sq3');
    }
    
    // create in memory database by default 
    $params = array (
    	#'dbname' => '/tmp/syncope_test.sq3',
    	'dbname' => ':memory:'
    );
    
    $db = Zend_Db::factory('PDO_SQLITE', $params);
    
    // enable foreign keys
    #$db->query('PRAGMA read_uncommitted = true');
    
    $db->query("CREATE TABLE IF NOT EXISTS `syncope_device` (
        `id` varchar(40) NOT NULL,
        `deviceid` varchar(64) NOT NULL,                                                                                                                                                                         
        `devicetype` varchar(64) NOT NULL,
        `policykey` varchar(64) DEFAULT NULL,
        `owner_id` varchar(40) NOT NULL,
        `useragent` varchar(255) DEFAULT NULL,
        `policy_id` varchar(40) DEFAULT NULL,
        `acsversion` varchar(40) DEFAULT NULL,
        `pinglifetime` int(11) DEFAULT NULL,
        `remotewipe` int(11) DEFAULT '0',
        `pingfolder` longblob,
        PRIMARY KEY (`id`)
	)");

    $db->query("CREATE TABLE IF NOT EXISTS `syncope_folder` (
        `id` varchar(40) NOT NULL,
        `device_id` varchar(40) NOT NULL,
        `class` varchar(64) NOT NULL,
        `folderid` varchar(254) NOT NULL,
        `parentid` varchar(254) DEFAULT NULL,
        `displayname` varchar(254) NOT NULL,
        `type` int(11) NOT NULL,
        `creation_time` datetime NOT NULL,
        `lastfiltertype` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE (`device_id`,`class`,`folderid`)
	)");
    
    $db->query("CREATE TABLE `syncope_synckey` (
    	`id` varchar(40) NOT NULL,
      	`device_id` varchar(40) NOT NULL,
      	`type` varchar(64) NOT NULL,
      	`counter` int(11) NOT NULL DEFAULT '0',
      	`lastsync` datetime NOT NULL,
      	`pendingdata` longblob,
      	PRIMARY KEY (`id`),
      	UNIQUE (`device_id`,`type`,`counter`)
    )");
    
    $db->query("CREATE TABLE `syncope_content` (
        `id` varchar(40) NOT NULL,
        `device_id` varchar(40) NOT NULL,
        `folder_id` varchar(40) NOT NULL,
        `contentid` varchar(64) NOT NULL,
        `creation_time` datetime NOT NULL,
        `creation_synckey` int(11) NOT NULL,
        `is_deleted` int(11) DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE (`device_id`,`folder_id`,`contentid`)
    )");
    
    return $db;
}