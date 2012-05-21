<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Zend Translation Adapter extension for PO files
 */
class Zend_Translate_Adapter_GettextPoTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {}

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {}

    public function testConstruct()
    {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles' . DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertTrue($translate instanceof Zend_Translate, 'failed to create instance');
    }
    
    public function testTranslationNochEinTest()
    {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Another test", $translate->_("Noch ein Test", "en"), 'Wrong translation!');
    }
    
    public function testTranslationHunde()
    {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Bunnys", $translate->_("Hunde", "en"), 'Wrong translation!');
    }
    
    public function testTranslationHundPlural()
    {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Dogs", $translate->plural("Hund", "Hunde", 3, "en"), 'Wrong translation!');
    }
    
    public function testTranslationHundSingular()
    {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Dog", $translate->plural("Hund","hunde", 1, "en"), 'Wrong translation!');
    }
    
      public function testTranslationHundSingular2()
     {
        $path = (dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Dog", $translate->_("Hund", "en"), 'This should not Fail any more! Error when translating singular of a plural without pluralfunction');
    }
}

