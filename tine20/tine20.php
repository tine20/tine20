#!/usr/bin/env php
<?php
/**
 * tine cli script 
 *
 * @package     Cli
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/**
 * path to tine 2.0 checkout
 */
$tine20path = dirname(__FILE__);

/**
 * anonymous methods (no pw/user required)
 */
$anonymousMethods = array('Tinebase.triggerAsyncEvents');

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
        'help|h'                => 'Display this help Message',
        'verbose|v'             => 'Output messages',
        'dry|d'                 => "Dry run - don't change anything",    
        'info|i'                => 'Get usage description of method',
    
        'method=s'              => 'Method to call [required]',              
        'username=s'            => 'Username [required]',              
        'password=s'            => 'Password',              
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   echo $e->getUsageMessage();
   exit;
}

if (count($opts->toArray()) === 0 || $opts->h || empty($opts->method) || (empty($opts->username) && ! in_array($opts->method, $anonymousMethods)) /*|| empty($opts->password)*/) {
    echo $opts->getUsageMessage();
    exit;
}

if (empty($opts->password) && ! in_array($opts->method, $anonymousMethods)) {
    fwrite(STDOUT, PHP_EOL . 'password> ');
    if (preg_match('/^win/i', PHP_OS)) {
        $pwObj = new Com('ScriptPW.Password');
        $passwordInput = $pwObj->getPassword();
    } else {
        system('stty -echo');
        $passwordInput = fgets(STDIN);
        system('stty echo');
    }
    
    $opts->password = rtrim($passwordInput);
}

Tinebase_Core::set('opts', $opts);
Tinebase_Core::dispatchRequest();
