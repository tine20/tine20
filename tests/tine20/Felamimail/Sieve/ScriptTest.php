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
    protected $_serializedSieveRule;
    
    protected $_smartSieveRuleFileInto = '#rule&&13&&ENABLED&&&&&&&&folder&&Listen/Icecast&&0&&List-Id&&icecast.xiph.org&&0';
    
    protected $_smartSieveRuleDiscard  = '#rule&&15&&ENABLED&&&&&&Bacula: Backup OK of&&discard&&&&0&&&&&&0';
    
    protected $_smartSieveVacation = '#vacation&&7&&"info@example.com"&&Thank you very much for your email.\n\n&&off';
    
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
        $rule      = new Felamimail_Sieve_Rule();
        $condition = new Felamimail_Sieve_Rule_Condition();
        $action    = new Felamimail_Sieve_Rule_Action();
        
        $condition->setComperator(Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS)
            ->setTest(Felamimail_Sieve_Rule_Condition::TEST_ADDRESS)
            ->setHeader('From')
            ->setKey('info@example.com');
        
        $action->setType(Felamimail_Sieve_Rule_Action::FILEINTO)
            ->setArgument('INBOX/UNITTEST');
            
        $rule->setEnabled(true)
            ->setId(12)
            ->setAction($action)
            ->addCondition($condition);
            
        $this->_serializedSieveRule = '#SieveRule' . serialize($rule);
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
     * test enabled rule
     */
    public function testEnabledRule()
    {
        $script    = new Felamimail_Sieve_Script();
        $rule      = new Felamimail_Sieve_Rule();
        $condition = new Felamimail_Sieve_Rule_Condition();
        $action    = new Felamimail_Sieve_Rule_Action();
        
        $condition->setComperator(Felamimail_Sieve_Rule_Condition::COMPERATOR_CONTAINS)
            ->setTest(Felamimail_Sieve_Rule_Condition::TEST_ADDRESS)
            ->setHeader('From')
            ->setKey('info@example.com');
        
        $action->setType(Felamimail_Sieve_Rule_Action::FILEINTO)
            ->setArgument('INBOX/UNITTEST');
            
        $rule->setEnabled(true)
            ->setId(12)
            ->setAction($action)
            ->addCondition($condition);
        
        $script->addRule($rule);
        
        $sieveScript = $script->getSieve();
        #echo $sieveScript;
        $this->assertContains('if allof (address :contains "From" "info@example.com")', $sieveScript);
        $this->assertContains('fileinto "INBOX/UNITTEST";', $sieveScript);
        $this->assertContains('Felamimail_Sieve_Rule', $sieveScript);
    }
    
    /**
     * test enabled vacation
     */
    public function testEnabledVacation()
    {
        $script = new Felamimail_Sieve_Script();
        
        $vacation = new Felamimail_Sieve_Vacation();
        
        $vacation->setEnabled(true)
            ->addAddress('info@example.com')
            ->setDays(8)
            ->setSubject('Lößlich')
            ->setFrom('sieve@example.com')
            ->setReason('Tine 2.0 Unit Test');
        
        $script->setVacation($vacation);
        
        $sieveScript = $script->getSieve();
        
        $this->assertContains(':days 8', $sieveScript);
        $this->assertContains(':from "sieve@example.com"', $sieveScript);
        $this->assertContains(':addresses ["info@example.com"]', $sieveScript);
        $this->assertContains('?Q?L=C3=B6=C3=9Flich?=', $sieveScript, $sieveScript);
        $this->assertContains('Felamimail_Sieve_Vacation', $sieveScript);
        $this->assertContains('Tine 2.0 Unit Test', $sieveScript);
    }
    
    /**
     * test disabled vacation
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
        
        $this->assertNotContains('vacation :days 8 :addresses ["info@example.com"]', $sieveScript);
        $this->assertContains('Felamimail_Sieve_Vacation', $sieveScript);
        $this->assertContains('Tine 2.0 Unit Test', $sieveScript);
    }

    /**
     * parse serialized sieve rule
     */
    public function testParseSerializedSieveRule()
    {
        $script = new Felamimail_Sieve_Script();
        
        $script->parseScript($this->_serializedSieveRule);
        $script->parseScript($this->_smartSieveRuleFileInto);
        $script->parseScript($this->_smartSieveRuleDiscard);
        $script->parseScript($this->_smartSieveVacation);
        
        $rules = $script->getRules();
        
        $this->assertEquals(3, count($rules));
    }
}
