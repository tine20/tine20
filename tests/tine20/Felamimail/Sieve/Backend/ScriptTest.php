<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Sieve_Backend_Script
 */
class Felamimail_Sieve_Backend_ScriptTest extends PHPUnit_Framework_TestCase
{
    /**
     * serialized rule
     * 
     * @var string
     */
    protected $_serializedSieveRule;
    
    /**
     * smart rule file into
     * 
     * @var string
     */
    protected $_smartSieveRuleFileInto = '#rule&&13&&ENABLED&&&&&&&&folder&&Listen/Icecast&&0&&List-Id&&icecast.xiph.org&&0';
    
    /**
     * smart rule discard
     * 
     * @var string
     */
    protected $_smartSieveRuleDiscard  = '#rule&&15&&ENABLED&&&&&&Bacula: Backup OK of&&discard&&&&0&&&&&&0';
    
    /**
     * sieve vacation
     * 
     * @var string
     */
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
        $script    = new Felamimail_Sieve_Backend_Script();
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
        $script = new Felamimail_Sieve_Backend_Script();
        $script->setVacation($this->_getVacation());
        
        $sieveScript = $script->getSieve();
        
        $this->assertContains(':days 8', $sieveScript);
        $this->assertContains(':from "sieve@example.com"', $sieveScript);
        $this->assertContains(':addresses ["info@example.com"]', $sieveScript);
        $this->assertContains('?Q?L=C3=B6=C3=9Flich?=', $sieveScript, $sieveScript);
        $this->assertContains('Felamimail_Sieve_Vacation', $sieveScript);
        $this->assertContains('Tine 2.0 Unit Test', $sieveScript);
    }
    
    /**
     * get vacation
     * 
     * @return Felamimail_Sieve_Vacation
     */
    protected function _getVacation()
    {
        $vacation = new Felamimail_Sieve_Vacation();
        
        $vacation->setEnabled(true)
            ->addAddress('info@example.com')
            ->setDays(8)
            ->setSubject('Lößlich')
            ->setFrom('sieve@example.com')
            ->setReason('Tine 2.0 Unit Test');
            
        return $vacation;
    }

    /**
     * test enabled vacation
     */
    public function testMimeVacation()
    {
        $vacation = $this->_getVacation();
        $vacation->setMime('multipart/alternative')->setReason('<html><body><strong>AWAY!</strong></body></html>');
        
        $script = new Felamimail_Sieve_Backend_Script();
        $script->setVacation($vacation);
        
        $sieveScript = $script->getSieve();
        
        $this->assertContains('Content-Type: multipart/alternative; boundary=foo', $sieveScript);
        $this->assertContains('vacation :days 8 :subject "=?UTF-8?Q?L=C3=B6=C3=9Flich?=" :from "sieve@example.com" :addresses ["info@example.com"] :mime text:', $sieveScript);
        $this->assertContains('<html><body><strong>AWAY!</strong></body></html>', $sieveScript);
        $this->assertContains('--foo--', $sieveScript);
    }
    
    /**
     * testStartAndEndDate
     * 
     * @see 0006266: automatic deactivation of vacation message
     */
    public function testStartAndEndDate()
    {
        $vacation = $this->_getVacation();
        $vacation->setStartdate('2012-05-08');
        $vacation->setEnddate('2012-05-18');
        $vacation->setDateEnabled(TRUE);
        
        $script = new Felamimail_Sieve_Backend_Script();
        $script->setVacation($vacation);
        $sieveScript = $script->getSieve();
        
        $this->assertContains('require ["vacation","date","relational"]', $sieveScript);
        $this->assertContains('if allof(currentdate :value "le" "date" "2012-05-18",', $sieveScript);
        $this->assertContains('currentdate :value "ge" "date" "2012-05-08")', $sieveScript);
    }
    
    /**
     * test disabled vacation
     */
    public function testDisabledVacation()
    {
        $script = new Felamimail_Sieve_Backend_Script();
        
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
        $script = new Felamimail_Sieve_Backend_Script();
        
        $script->setScriptToParse($this->_serializedSieveRule);
        $script->readScriptData();
        $script->setScriptToParse($this->_smartSieveRuleFileInto);
        $script->readScriptData();
        $script->setScriptToParse($this->_smartSieveRuleDiscard);
        $script->readScriptData();
        $script->setScriptToParse($this->_smartSieveVacation);
        $script->readScriptData();
        
        $rules = $script->getRules();
        
        $this->assertEquals(3, count($rules));
    }
}
