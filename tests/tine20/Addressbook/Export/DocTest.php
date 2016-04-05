<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

use \PhpOffice\PhpWord;

/**
 * Addressbook Doc generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Addressbook_Export_DocTest extends TestCase
{
    public function testExportLetter()
    {
        // skip tests for php7
        // ERROR: PHP Fatal error:  Cannot use PhpOffice\PhpWord\Shared\String as String because 'String' is a special
        //  class name in /usr/local/share/tine20.git/tine20/vendor/phpoffice/phpword/src/PhpWord/TemplateProcessor.php
        //  on line 23
        if (PHP_VERSION_ID >= 70000) {
            $this->markTestSkipped('FIXME 0011730: fix doc export for php7');
        }

        // make sure definition is imported
        $definitionFile = __DIR__ . '/../../../../tine20/Addressbook/Export/definitions/adb_default_doc.xml';
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFile, $app);

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'James')
        ));
        $doc = new Addressbook_Export_Doc($filter);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));
    }

    // read and write sucks
    public function _testReadWriteCycleSucks()
    {
        PhpWord\Settings::setTempDir(Tinebase_Core::getTempDir());

        $source = str_replace('tests/tine20', 'tine20', __DIR__) . '/templates/addressbook_contact_letter.docx';
        $phpWord = PhpWord\IOFactory::load($source);

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $writer = $phpWord->save($tempfile);

        `open $tempfile`;
    }
}
