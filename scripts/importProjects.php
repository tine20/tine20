#!/usr/bin/env php
<?php
/**
 * import projects
 * - copy this to your tine20 root dir
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * usage: ./importProjects.php 
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

set_include_path(dirname(__FILE__) .'/Zend' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$importer = new Timetracker_Setup_Import_Egw14();
$importer->import();

?>
