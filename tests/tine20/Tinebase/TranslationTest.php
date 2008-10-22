<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_TranslationTest::main();
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_TranslationTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_TranslationTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setUp()
    {
        
    }
    
    /**
     * test Tinebase_Translation::getTranslationDirs()
     * 
     * All translation dirs from apps having translations should be returned
     *
     */
    public function testGetTranslationDirs()
    {
        $translationDirs = Tinebase_Translation::getTranslationDirs();
        
        $this->assertTrue(isset($translationDirs['Tinebase']), 'Tinebase is missing');
        $this->assertGreaterThan(5, count($translationDirs), 'Not all translationdirs where found');
        $this->assertTrue((bool)preg_match("/\/Tinebase\/translations$/", $translationDirs['Tinebase']), 'translation dir must end with /translations');
    }
    
    /**
     * test Tinebase_Translation::getPoTranslationFiles
     * 
     * All po files of a given locale should be returned
     *
     */
    public function testGetPoTranslationFiles()
    {
        $locale = Zend_Registry::get('locale');
        $poTranslationFiles = Tinebase_Translation::getPoTranslationFiles($locale);
        
        $this->assertTrue(isset($poTranslationFiles['Tinebase']), 'Tinebase is missing');
        $this->assertGreaterThan(5, count($poTranslationFiles), 'Not all translationfiles where found');
    }
    
    /**
     * test Tinebase_Translation::getJsTranslations
     * 
     * js translation code for generic,ext and tine must be inclueded
     *
     */
    public function testGetJsTranslations()
    {
        $locale = Zend_Registry::get('locale');
        $jsTranslations = Tinebase_Translation::getJsTranslations($locale);
        
        $this->assertTrue((bool)preg_match("/Locale\.prototype\.TranslationLists/", $jsTranslations), 'generic translations are missing');
        $this->assertTrue((bool)preg_match("/Ext\.UpdateManager\.defaults\.indicatorText/", $jsTranslations), 'ext translations are missing');
        $this->assertTrue((bool)preg_match("/Locale\.Gettext\.prototype\._msgs\['\.\/LC_MESSAGES\/Tinebase'\]/", $jsTranslations), 'tine translations are missing');
    }
}

