#!/usr/bin/env php
<?php
/**
 * update svn externals
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 * usage: ./updateExternals.php svn_checkout_dir 
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

$dir = $argv[1];

if (empty($dir)) {
    die("You need to set a directory.\n");
}

//$branch = 'branches/2008-summer';
define('TRUNK', 'trunk');
define('BASEURL', 'https://svn.tine20.org/svn/');

// get branch/release
$branchUrl = system("svn info " . $dir . " | grep 'URL'");
$branch = str_replace("URL: " . BASEURL, "", $branchUrl);

// get externals
$externals = array();
exec("svn status " . $dir . " | grep 'X'", $externals);

//print_r($externals);
$propsets = array();
foreach ($externals as $ext) {

	// cut last dir of ext path
    $ext = str_replace("X  ", "", $ext);
    preg_match("/([\w\/\-]+)\/([\w\-]+)$/", $ext, $match);
    $extPath = $match[1];
    $dir = $match[2];

	// svn info to get path
	//echo "svn info " . $ext;
    $url = system("svn info " . $ext . " | grep 'URL'");
		
    // cut last dir of svn url
    $url = str_replace("URL: ", "", $url);
    preg_match("/([\w\/\.:\-]+)\/([\w\-]+)$/", $url, $match);
    $urlPath = $match[0];			
		
	// replace trunk with branch in svn path
	$newPath = str_replace(TRUNK, $branch, $urlPath);
	
	$propsets[$extPath] .= "$dir $newPath \r\n";
}

foreach ($propsets as $path => $props) {
    $props = trim($props);
	$command = "svn propset svn:externals '$props' $path";
	//echo $command."\n";

    system($command);
}
	
// commit
// system('svn ci -m "updated externals" '.$dir);

?>
