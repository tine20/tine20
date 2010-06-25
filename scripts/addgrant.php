#!/usr/bin/env php
<?php
/**
 * Tine 2.0 add grant script
 * - This script adds a defined grant for all containers of a group in an application 
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

// db info
$host   = 'host';
$user   = 'user';
$pass   = 'pass';
$db     = 'db';

// values
$containerTable = 'tine20_container';
$aclTable       = 'tine20_container_acl';
$grant          = 'deleteGrant';
$accountId      = 'f68dbbc2b4ad4e823423962f2b1a2e78ea1703e4';
$accountType    = 'group';
$appId          = '3584703dcd783a3427a1d9a2cf0352327a6d4ca7';

$link = mysql_connect($host, $user, $pass)
    or die("No connection: " . mysql_error());
mysql_select_db($db) or die("Could not select DB.");

// get all containers where this group has access
$query = 'SELECT DISTINCT container.id FROM `' . $containerTable . '` as container left join ' . $aclTable . ' as acl on container.id = acl.container_id '
    . 'WHERE application_id = \'' . $appId . '\' and account_id = \'' . $accountId . '\' and account_type = \'' . $accountType . '\'';

$result = mysql_query($query) or die("\n" . mysql_error());

while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $lines[] = $line;
}

//print_r($lines);

echo 'Adding ' . count($lines) . ' new grants for ' . $accountType . ' ' . $accountId;
foreach($lines as $line) {
    $id = sha1(mt_rand(). microtime());
    $query = " 
    INSERT INTO $aclTable
        (`id` ,
        `container_id` ,
        `account_type` ,
        `account_id` ,
        `account_grant`
        ) VALUES (
        '$id',  '" . $line['id'] . "',  '$accountType',  '$accountId',  '$grant')";
    //echo $query;
    $result = mysql_query($query) or die("\n" . mysql_error() . "\n");
    if ($result) {
        echo ".";
    }
}
echo " done.\n";

mysql_close($link);
