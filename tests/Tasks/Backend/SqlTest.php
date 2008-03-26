<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tasks_Backend_SqlTest::main');
}

/**
 * Test class for Tinebase_Account
 */
class Tasks_Backend_SqlTest extends PHPUnit_Framework_TestCase
{

}		
	

if (PHPUnit_MAIN_METHOD == 'Tasks_Backend_SqlTest::main') {
    Tasks_Backend_SqlTest::main();
}
