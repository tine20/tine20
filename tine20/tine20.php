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
    die('Not allowed: wrong sapi name!');
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

if (count($opts->toArray()) === 0 || $opts->h || empty($opts->method)) {
    echo $opts->getUsageMessage();
    exit;
}

// get username / password if not already set
if (! in_array($opts->method, $anonymousMethods)) {
    if (empty($opts->username)) {
        $opts->username = Tinebase_Server_Cli::promptInput('username');
    }
    if (empty($opts->username)) {
        echo "error: username must be given! exiting \n";
        exit (1);
    }
    
    if (empty($opts->password)) {
        $opts->password = Tinebase_Server_Cli::promptInput('password', TRUE);
    }
}

Tinebase_Core::set('opts', $opts);
Tinebase_Core::dispatchRequest();
