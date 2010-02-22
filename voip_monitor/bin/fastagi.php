#!/usr/bin/env php
<?php
/**
 * FastAGI Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

$baseIncludePath = dirname(dirname(__FILE__));
set_include_path($baseIncludePath . PATH_SEPARATOR . $baseIncludePath . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$fastAgi = new FastAGI();

$fastAgi->run();
