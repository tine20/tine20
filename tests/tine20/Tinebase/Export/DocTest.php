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
class Tinebase_Export_DocTest extends TestCase
{
    public function testDocTwigFunctions()
    {
        /** @var Addressbook_Export_Doc $export */
        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'doc',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_doc.xml',
                'template'           => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions.docx',
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());

        $export->generate();
        $tmpFile = Tinebase_TempFile::getTempPath();
        $export->save($tmpFile);

        try {
            static::assertEquals(filesize(dirname(__DIR__) . '/files/export/twigFunctions_result.docx'),
                filesize($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testDocTwigFunctions2()
    {
        $export = new Tinebase_Export_Doc2(new Addressbook_Model_ContactFilter(), null, [
            'definitionFilename'=> dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_doc.xml',
            'template'          => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions2.docx',
            'recordData'        => [
                'n_given'           => 'testName',
                'n_family'          => 'moreTest',
                'bday'              => '2000-01-02'
            ]]);
        $export->generate();
        $tmpFile = Tinebase_TempFile::getTempPath();
        $export->save($tmpFile);

        try {
            static::assertEquals(filesize(dirname(__DIR__) . '/files/export/twigFunctions_result2.docx'),
                filesize($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testDocConvertToPdf()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS} != true
            || Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERSION}  < 2
        ) {
            $this->markTestSkipped('no docservice configured');
        }

        $export = Tinebase_Export::factory(new Addressbook_Model_ContactFilter(),
            [
                'format'             => 'doc',
                'definitionFilename' => dirname(__DIR__, 4) . '/tine20/Addressbook/Export/definitions/adb_doc.xml',
                'template'           => dirname(__DIR__) . '/files/export/addressbook_contact_twigFunctions.docx',
                'recordData'         => [
                    'n_given'       => 'testName',
                    'n_family'      => 'moreTest',
                    'bday'          => '2000-01-02'
                ]
            ], Addressbook_Controller_Contact::getInstance());

        $export->generate();

        $file = null;
        try {
            $file = $export->convert(Tinebase_Export_Convertible::PDF);
            $this->assertEquals('application/pdf', mime_content_type($file));

        } finally {
            if ($file) {
                unlink($file);
            }
        }
    }
}