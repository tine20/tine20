<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        make this work again (when setup tests have been moved)
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_CoreTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Setup_CoreTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function testGetConfigFilePath()
    {
        $configFilePath = Setup_Core::getConfigFilePath();
        $this->assertTrue(file_exists($configFilePath));
//        rename($configFilePath, $configFilePath.'.phpunit');
//        var_dump(Setup_Core::getConfigFilePath());
//        $this->assertNull(Setup_Core::getConfigFilePath());
//        rename($configFilePath.'.phpunit', $configFilePath);
    }
    
    public function testConfigFilesExists()
    {
        $this->assertTrue(Setup_Core::configFileExists());
    }
}
