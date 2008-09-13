#!/usr/bin/env php
<?php
/**
 * Tine 2.0 - this file starts the setup process
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
define ( 'IMPORT_INITIALDATA', TRUE );
define ( 'IMPORT_EGW_14', FALSE );
define ( 'IMPORT_EGW_14_ADDRESSBOOK', FALSE );
define ( 'IMPORT_TINE_REV_949', FALSE );

/**
 * initialize autoloader
 */
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

$configPath = dirname(__FILE__) . '/config.inc.php';

/**
 * cli api
 */
if (php_sapi_name() == 'cli') {
    try {
        $opts = new Zend_Console_Getopt(
        array(
            'config|c=s'      => 'Path to config.inc.php file',
            'help|h'          => 'Display this help Message',
        ));
        $opts->parse();
    } catch (Zend_Console_Getopt_Exception $e) {
       echo $e->getUsageMessage();
       exit;
    }
    
    if (count($opts->toArray()) === 0 || $opts->h) {
        echo $opts->getUsageMessage();
        exit;
    }
    
    if($opts->c) {
        $configPath = $opts->c;
    }
}


/**
 * load central configuration once and put it in the registry
 */
try {
    if(file_exists($configPath)) {
        $config = new Zend_Config(require $configPath);
    } else {
        die ('central configuration file ' . $configPath . ' not found.<br>Did you migrate from config.ini to config.inc.php?');
    }
    Zend_Registry::set('configFile', $config);
} catch (Zend_Config_Exception $e) {
    die ('central configuration file ' . $configPath . ' not found or invalid');
}
    
/**
 * validate environemnt
 * @todo include ini checks in php envirionment checks
 */
$check = new Setup_ExtCheck('Setup/essentials.xml');

$output = $check->getOutput();
echo $output;

if (strpos($output, "FAILURE")) {
    phpinfo();
    die("Unsufficent server system.");
}

// check php environment
$requiredIniSettings = array(
    'magic_quotes_sybase'  => 0,
    'magic_quotes_gpc'     => 0,
    'magic_quotes_runtime' => 0,
);

foreach ($requiredIniSettings as $variable => $newValue) {
    $oldValue = ini_get($variable);
    if ($oldValue != $newValue) {
        if (ini_set($variable, $newValue) === false) {
            die("Sorry, your environment is not supported. You need to set $variable from $oldValue to $newValue.");
        }
    }
}

/**
 * Check ExtJS
 */
$tine20dir = dirname(__FILE__);
if (!file_exists("$tine20dir/ExtJS/ext-all.js")) {
    die("You need to put a copy of <a href='http://extjs.com/products/extjs/download.php'>ExtJS</a> in $tine20dir");
}
/**
 * start setup
 */
$setup = new Setup_Controller();

/**
 * update already installed applications
 */
$applicationTable = NULL;
try {
    // get list of applications, sorted by id. Tinebase should have the smallest id because it got installed first.
    $applicationTable = Zend_Registry::get('dbAdapter')->describeTable(SQL_TABLE_PREFIX . 'applications');
} catch (Zend_Db_Statement_Exception $e) {
    // do nothing, no applications installed
}    

if(is_array($applicationTable)) {
    $applications = Tinebase_Application::getInstance()->getApplications(NULL, 'id');
    $setup->updateApplications($applications);
}


/**
 * install new applications 
 */ 
$setup->installApplications('Tinebase/Setup/setup.xml', '/Setup/setup.xml');

/**
 * fill with default or present values 
 */
if($setup->initialLoadRequired()) {

    # either import data from eGroupWare 1.4 or tine 2.0 revision 949    
    if (IMPORT_INITIALDATA === TRUE) {
        $import = new Setup_Import_TineInitial();
    } elseif (IMPORT_EGW_14 === TRUE) {
        $import = new Setup_Import_Egw14();
    } elseif (IMPORT_TINE_REV_949 === TRUE) {
        $import = new Setup_Import_TineRev949();
    }
    $import->import();
}

/**
 * import other data (from single tables)
 */
if ( IMPORT_EGW_14_ADDRESSBOOK === TRUE ) {
    $import = new Setup_Import_Egw14();
    $import->importAddressbook('egw_addressbook', FALSE);
}

echo "setup done<br>";