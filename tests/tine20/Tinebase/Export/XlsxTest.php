<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Export_Doc
 *
 * @package     Tinebase
 */
class Tinebase_Export_XlsxTest extends TestCase
{

    public function testXlsxTwigFunctions()
    {
        /** @var Addressbook_Export_Xls $export */
        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'xls',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_xls.xml',
                'template'           => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions.xlsx',
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());

        $export->generate();
        $tmpFile = Tinebase_TempFile::getTempPath();
        $export->write($tmpFile);

        try {
            static::assertEquals(filesize(dirname(__DIR__) . '/files/export/twigFunctions_result.xlsx'),
                filesize($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }
}