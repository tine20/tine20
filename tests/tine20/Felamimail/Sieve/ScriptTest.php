<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Sieve_ScriptTest::main');
}

/**
 * Test class for Felamimail_Sieve_Script
 */
class Felamimail_Sieve_ScriptTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Sieve Script Tests');
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }


    /**
     * test enabled vacation
     *
     */
    public function testEnabledVacation()
    {
        $script = new Felamimail_Sieve_Script();
        
        $vacation = new Felamimail_Sieve_Vacation();
        
        $vacation->setEnabled(true)
            ->addAddress('info@example.com')
            ->setDays(8)
            ->setReason('Tine 2.0 Unit Test');
        
        $script->setVacation($vacation);
        
        $sieveScript = $script->getSieve();
        
        #echo $sieveScript;
        
        $this->assertContains('vacation :days', $sieveScript);
        $this->assertContains('Felamimail_Sieve_Vacation', $sieveScript);
        $this->assertContains('Tine 2.0 Unit Test', $sieveScript);
    }
    
    /**
     * test disabled vacation
     *
     */
    public function testDisabledVacation()
    {
        $script = new Felamimail_Sieve_Script();
        
        $vacation = new Felamimail_Sieve_Vacation();
        
        $vacation->setEnabled(false)
            ->addAddress('info@example.com')
            ->setDays(8)
            ->setReason('Tine 2.0 Unit Test');
        
        $script->setVacation($vacation);
        
        $sieveScript = $script->getSieve();
        
        #echo $sieveScript;
        
        $this->assertNotContains('vacation :days', $sieveScript);
        $this->assertContains('Felamimail_Sieve_Vacation', $sieveScript);
        $this->assertContains('Tine 2.0 Unit Test', $sieveScript);
    }
    
}
