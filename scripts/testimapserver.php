<?php
/**
 * Tine 2.0 IMAP server test script
 * - This script is doing some tests against your imap server.
 * - Copy this to your tine20 root dir and open it in your browser.
 *
 * @package     HelperScripts
 * @subpackage  Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        check acl
 * @todo        check subscriptions
 * @todo        check quota
 */

die("<center>THIS SCRIPT IS DISABLED FOR SECURITY REASONS.<br><br><b>PLEASE COMMENT OUT LINE ". __LINE__ ." TO ENABLE THIS SCRIPT</b>.</center>");

/*********** IMAP config ***********/

$imapConfig =  array (
    'host' => 'xxx',
    'user' => 'xxx',
    'password' => 'xxx',
    'port' => 143,
    'ssl' => 'tls',
);

$subfoldersOf = 'INBOX';

/*********** environment ***********/

set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/*********** start ***********/

$startTime = microtime(true);

print "<pre>";

print "<h1><span style='color:red;'>ATTENTION: THIS OUTPUT CONTAINS YOUR USERNAME AND PASSWORD!!!</span></h1>";

/*********** login and select INBOX ***********/

$elapsedTime = microtime(true) - $startTime;
print "<h1> $elapsedTime :: Login with config: " . print_r($imapConfig, TRUE) . "</h1>";

$imapBackend = new Felamimail_Backend_Imap($imapConfig);

/*********** show capabilities and namespace **/

$capabilities = $imapBackend->getCapabilityAndNamespace();

$elapsedTime = microtime(true) - $startTime;
print "<h1> $elapsedTime :: get capabilities + namespace: " . print_r($capabilities, TRUE) . "</h1>";

/*********** get all folders *****************/

$folders = $imapBackend->getFolders();

$elapsedTime = microtime(true) - $startTime;
print "<h1> $elapsedTime :: get folders: " . print_r($folders, TRUE) . "</h1>";

/*********** get subfolders *****************/

$delimiter = $folders[$subfoldersOf]['delimiter'];
$subfolders = $imapBackend->getFolders($subfoldersOf . $delimiter, '%');

$elapsedTime = microtime(true) - $startTime;
print "<h1> $elapsedTime :: get subfolders: " . print_r($subfolders, TRUE) . "</h1>";

/*********** logout ************/

$elapsedTime = microtime(true) - $startTime;
print "<h1> $elapsedTime :: Logout</h1>";

print "</pre>";
