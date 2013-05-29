<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Voipmanager_SnomTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Voipmanager_Frontend_Snom
     */
    protected $_snom = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $this->_snom = new Voipmanager_Frontend_Snom();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test creation of asterisk context
     *
     */
    public function testSettings()
    {
        // create phone
        $jsonTest = new Voipmanager_JsonTest();
        $jsonTest->setUp();
        $voipmanagerJson = new Voipmanager_Frontend_Json();
        $phoneData = $jsonTest->getSnomPhone();
        $phone = $voipmanagerJson->saveSnomPhone($phoneData, array(), array());
        
        // set some required server vars
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; snom320-SIP 7.1.30';
        $_SERVER["REMOTE_ADDR"] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        
        // buffer output and call settings()
        ob_start();
        $this->_snom->settings($phone['macaddress']);
        $out = ob_get_clean();
        
        $this->assertContains('<settings><phone-settings><firmware_interval perm="RO">0</firmware_interval><update_policy perm="RO">auto_update</update_policy>', $out);
        $this->assertContains('<transfer_on_hangup perm="RO">on</transfer_on_hangup>', $out);
    }
}
