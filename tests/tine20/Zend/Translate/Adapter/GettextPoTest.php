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
        $obj = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po');
        $this->assertTrue($obj instanceof Zend_Translate, 'failed to create instance');
    }
    
    public function testTranslationNochEinTest()
    {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po', 'en');
        $this->assertEquals($translate->_("Noch ein Test"), "Another test", 'Wrong translation!');
    }
    
    public function testTranslationHunde()
    {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po', 'en');
        $this->assertEquals($translate->_("Hunde"), "Bunnys", 'Wrong translation!');
    }
    
    public function testTranslationHundPlural()
    {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po', 'en');
        $this->assertEquals($translate->plural("Hund", "Hunde", 3), "Dogs", 'Wrong translation!');
    }
    
    public function testTranslationHundSingular()
    {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po', 'en');
        $this->assertEquals($translate->plural("Hund","hunde", 1), "Dog", 'Wrong translation!');
    }
    
      public function testTranslationHundSingular2()
     {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/test.po', 'en');
        $this->assertFalse($translate->_("Hund") == "Dog", 'This should Fail! Error when translating singular of a plural without pluralfunction');
    }
    
    public function testWithoutFilename()
    {
        $translate = new Zend_Translate('gettextPo', '/var/www/tine20/tine20/tests/tine20/Zend/Translate/TestFiles/', 'en');
        $this->assertEquals($translate->_("Noch ein Test"), "Another test", 'Wrong translation!');
    }
}

