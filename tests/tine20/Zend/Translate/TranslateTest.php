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
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Zend Translation Adapter extension for PO files
 */
class Zend_Translate_TranslateTest extends PHPUnit_Framework_TestCase
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

    public function testTranslationPluralWithSingular1()
     {
        $path = (dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettext', $path, 'en');
        $this->assertEquals("Dog", $translate->_("Hund", "en"), 'Translating singular of a plural without pluralfunction');
    }
    
    public function testTranslationPluralWithSingular2()
    {
        $path = (dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Dog", $translate->_("Hund", "en"), 'Translating singular of a plural without pluralfunction');
    }
    
    public function testTranslationPluralWithPlural()
    {
        $path = (dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Dog", $translate->plural("Hund","hunde", 1, "en"), 'Translating singular of a plural with pluralfunction');
    }
    
    public function testTranslationNotExist()
    {
        $path = (dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestFiles'. DIRECTORY_SEPARATOR;
        $translate = new Zend_Translate('gettextPo', $path, 'en');
        $this->assertEquals("Gans", $translate->_("Gans", "en"), 'No translation for Gans');
    }
}

