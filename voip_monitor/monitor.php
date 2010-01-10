#!/usr/bin/env php
<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Exception.php 5149 2008-10-29 12:07:26Z p.schuele@metaways.de $
 */

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
        'help|h'                => 'Display this help Message',
        'verbose|v'             => 'Output messages',
        'config=s'              => 'path to configuration file (defaults to /etc/monitor.ini)',
        'daemon|d'              => 'become a daemon'              
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   fwrite(STDOUT, $e->getUsageMessage());
   exit(1);
}

if ($opts->h) {
    fwrite(STDOUT, $opts->getUsageMessage());
    exit;
}

$configPath   = $opts->config ? $opts->config : '/etc/monitor.ini';
$becomeDaemon = $opts->daemon ? true          : false;

$config = new Zend_Config_Ini($configPath, 'general');

$voipMonitor = new VoipMonitor($config, $becomeDaemon);
$voipMonitor->run();

