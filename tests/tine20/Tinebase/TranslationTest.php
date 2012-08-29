<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        //Some tests may have changed the User Locale => restore defaults
        Tinebase_Core::setupUserLocale();
    }
    
    public function tearDown()
    {
        //Some tests may have changed the User Locale => restore defaults
        Tinebase_Core::setupUserLocale();
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
        $poTranslationFiles = Tinebase_Translation::getPoTranslationFiles(array(
            'locale' => (string) Zend_Registry::get('locale'),
        ));
        
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
    
    /**
     * test getCountryList
     */
    public function testGetCountryList()
    {
        Tinebase_Core::setupUserLocale('de_DE');
        $countries = Tinebase_Translation::getCountryList();
        $this->assertTrue(is_array($countries));
        $failure = true;
        foreach ($countries['results'] as $country) {
            if ($country['shortName'] == 'DE') {
                $this->assertEquals('Deutschland', $country['translatedName']);
                $failure = false;
            }
        }
        if ($failure) {
            $this->fail('The result of Tinebase_Translation::getCountryList does not contain country with shortName "DE"');
        }
        
        Tinebase_Core::setupUserLocale('en_US');
        $countries = Tinebase_Translation::getCountryList();
        $this->assertTrue(is_array($countries));
        $failure = true;
        foreach ($countries['results'] as $country) {
            if ($country['shortName'] == 'DE') {
                $this->assertEquals('Germany', $country['translatedName']);
                $failure = false;
            }
        }
        if ($failure) {
            $this->fail('The result of Tinebase_Translation::getCountryList does not contain country with shortName "DE"');
        }

    }
    
    /**
     * test getCountryNameByRegionCode
     */
    public function testGetCountryNameByRegionCode()
    {
        Tinebase_Core::setupUserLocale('de_DE');
        $this->assertEquals('Deutschland', Tinebase_Translation::getCountryNameByRegionCode('DE'));
        $this->assertEquals('Vereinigte Staaten', Tinebase_Translation::getCountryNameByRegionCode('US'));
        $this->assertNull(Tinebase_Translation::getCountryNameByRegionCode('XX'));
        
        Tinebase_Core::setupUserLocale('en_US');
        $this->assertEquals('Germany', Tinebase_Translation::getCountryNameByRegionCode('DE'));
        $this->assertEquals('United States', Tinebase_Translation::getCountryNameByRegionCode('US'));
    }
    
    /**
     * test getRegionCodeByCountryName
     */
    public function testGetRegionCodeByCountryName()
    {
        Tinebase_Core::setupUserLocale('de_DE');
        $this->assertEquals('DE', Tinebase_Translation::getRegionCodeByCountryName('Deutschland'));
        $this->assertEquals('US', Tinebase_Translation::getRegionCodeByCountryName('Vereinigte Staaten'));
        $this->assertNull(Tinebase_Translation::getRegionCodeByCountryName('XX'));
        
        Tinebase_Core::setupUserLocale('en_US');
        $this->assertEquals('DE', Tinebase_Translation::getRegionCodeByCountryName('Germany'));
        $this->assertEquals('US', Tinebase_Translation::getRegionCodeByCountryName('United States'));
    }
    
    public function testCustomTranslations()
    {
        $lang = 'en_GB';
        $translationPath = Tinebase_Core::getTempDir() . "/tine20/translations";
        Tinebase_Config::getInstance()->translations = $translationPath;
        
        
        $translationDir = "$translationPath/$lang/Tinebase/translations";
        @mkdir($translationDir, 0777, TRUE);
        
        $poFile = "$translationDir/$lang.po";
        $poData = 
'msgid ""
msgstr ""
"Project-Id-Version: Tine 2.0 - Tinebase\n"
"POT-Creation-Date: 2008-05-17 22:12+0100\n"
"PO-Revision-Date: 2008-07-29 21:14+0100\n"
"Last-Translator: Cornelius Weiss <c.weiss@metaways.de>\n"
"Language-Team: Tine 2.0 Translators\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Poedit-Language: en\n"
"X-Poedit-Country: US\n"
"X-Poedit-SourceCharset: utf-8\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"
"X-Tine20-Language: My Language\n"
"X-Tine20-Country: MY REGION\n"

#: Acl/Rights/Abstract.php:75
msgid "run"
msgstr "изпълни"
';
        file_put_contents($poFile, $poData);
        
        $availableTranslations = Tinebase_Translation::getAvailableTranslations();
        foreach($availableTranslations as $langInfo) {
            if ($langInfo['locale'] == $lang) {
                $customInfo = $langInfo;
            }
        }
        
        // assert cutom lang is available
        $this->assertTrue(isset($customInfo), 'custom translation not in list of available translations');
        $this->assertEquals('My Language', $customInfo['language'], 'custom language param missing');
        $this->assertEquals('MY REGION', $customInfo['region'], 'custom region param missing');
        
        // test the translation
        $translation = Tinebase_Translation::getTranslation('Tinebase', new Zend_Locale($lang));
        // NOTE: Zent_Translate does not work with .po files
        //$this->assertEquals("изпълни", $translation->_('run'));
        
        $jsTranslations = Tinebase_Translation::getJsTranslations($lang, 'Tinebase');
        $this->assertEquals(1, preg_match('/изпълни/', $jsTranslations));
        
        Tinebase_Core::setupUserLocale();
        
    }
    
    /**
    * test SingularExistence
    */
    public function testSingularExistence()
    {
        $jsTranslations = Tinebase_Translation::getJsTranslations('de', 'Tinebase');
        $this->assertContains(', "Deleting Tag"', $jsTranslations, 'Singular of "Deleting Tag, Deleting Tags" is missing!');
    }

    /**
    * test french translations singular
    * 
    * @see 0007014: Dates not formatted
    */
    public function testFrench()
    {
        Tinebase_Core::getCache()->clean();
        $jsTranslations = Tinebase_Translation::getJsTranslations('fr', 'Tinebase');
        $this->assertTrue(preg_match("/: \"liste \\\\\"\n/", $jsTranslations, $matches) === 0, 'Translation string missing / preg_match fail: ' . print_r($matches, TRUE));
        $this->assertContains(': "liste \"à faire\""', $jsTranslations, 'Could not find french singular of "todo lists"');
    }
}
