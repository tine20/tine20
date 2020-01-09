<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Test class for Tinebase_Group
 */
class Tinebase_TranslationTest extends TestCase
{
    public function setUp()
    {
        // Some tests may have changed the User Locale => restore defaults
        Tinebase_Core::setupUserLocale();
    }
    
    public function tearDown()
    {
        // Some tests may have changed the User Locale => restore defaults
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
    
    /**
     * try to translate something
     */
    public function testTranslation()
    {
        // test the translation
        $translation = Tinebase_Translation::getTranslation('Tinebase', new Zend_Locale('de'));
        
        $this->assertEquals('Fehler melden', $translation->_('Report bugs'));
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

    /**
     * testTranslationFiles
     * 
     * @see 0009864: add translations check to unittests
     */
    public function testTranslationFiles()
    {
        if (! file_exists('/usr/bin/msgfmt')) {
            $this->markTestSkipped('msgfmt (gettext) executable required for this test');
        }

        $tineRoot = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20';
        exec('for i in `ls ' . $tineRoot . '/*/translations/*.po`; do msgfmt -o - --strict $i 2>&1 1>/dev/null ; done', $output);
        
        $this->assertEquals(0, count($output), 'Found invalid translation file(s): ' . print_r($output, true));
    }

    /**
     * check if lang helper is outputting usage information
     *
     * TODO add more langHelper functionality tests
     * @group nogitlabci
     */
    public function testLangHelperUsageInfo()
    {
        $cmd = realpath(__DIR__ . '/../../../tine20/langHelper.php');
        $cmd = TestServer::assembleCliCommand($cmd);
        exec($cmd, $output);

        $this->assertContains('langHelper.php [ options ]', $output[0]);
    }

    public function testExtraTranslations()
    {

        $tinebaseTranslationsDir = realpath(__DIR__ . '/../../../tine20/Tinebase/translations');
        $extraTranslationsDir = $tinebaseTranslationsDir . '/extra/Addressbook';
        @mkdir($extraTranslationsDir, 0777, true);

        if (! is_dir($extraTranslationsDir)) {
            $this->markTestSkipped('no write access to code');
        }

        $poFile = $extraTranslationsDir . '/de_DE.po';
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
"X-Poedit-Language: de\n"
"X-Poedit-Country: DE\n"
"X-Poedit-SourceCharset: utf-8\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"
"X-Tine20-Language: My Language\n"
"X-Tine20-Country: MY REGION\n"

#: Acl/Rights/Abstract.php:75
msgid "run"
msgstr "изпълни"
';
        file_put_contents($poFile, $poData);
        `cd $extraTranslationsDir && msgfmt -o de_DE.mo de_DE.po`;

        Tinebase_Core::getCache()->clean();

        // test the translation
        $translation = Tinebase_Translation::getTranslation('Addressbook', new Zend_Locale('de_DE'));
        $this->assertEquals("изпълни", $translation->_('run'));

        $jsTranslations = Tinebase_Translation::getJsTranslations('de_DE', 'Addressbook');
        $this->assertEquals(1, preg_match('/изпълни/', $jsTranslations));

        // cleanup
        `rm -Rf $extraTranslationsDir`;
    }
}
