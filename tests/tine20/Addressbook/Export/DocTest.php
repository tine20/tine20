<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */


/**
 * Addressbook Doc generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_DocTest extends TestCase
{
    protected function _genericExportTest($_config)
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsql renders some small differences, so md5 checksum doesnt match. But the doc files look more or less ok?');
        }
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $definition = Tinebase_ImportExportDefinition::getInstance()
            ->updateOrCreateFromFilename($_config['definition'], $app);

        $config = new SimpleXMLElement($definition->plugin_options);
        $config->addChild('template', $_config['template']);
        $config->addChild('group', 'n_given');
        $definition->plugin_options = $config->asXML();
        Tinebase_ImportExportDefinition::getInstance()->update($definition);

        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomery',
            'n_given'           => 'Paul',
            'n_family'          => 'test',
            'email'             => 'tmp@test.de'
        )));
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomery')
        ));
        if (isset($_config['exportClass'])) {
            $class = $_config['exportClass'];
        } else {
            $class = 'Addressbook_Export_Doc';
        }
        /** @var Tinebase_Export_Doc $doc */
        $doc = new $class($filter, Addressbook_Controller_Contact::getInstance(), array(
            'definitionId' => $definition->getId(),
            'sortInfo' => array('field' => 'tel_work')
        ));
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), $_config['filename']) . '.docx';
        $doc->save($tempfile);

        $expectedFile = dirname($_config['template']) . '/results/' . basename($_config['template']);
        $contentHashIs = hash_file('md5', 'zip://' . $tempfile . '#word/document.xml');
        $contentHashToBe = hash_file('md5', 'zip' . substr($expectedFile, strpos($expectedFile, ':')) . '#word/document.xml');
        static::assertEquals($contentHashToBe, $contentHashIs, 'generated document does not match expectation');
    }

    public function testRecordBlock()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_record_block.xml',
            'template' => 'file://' . __DIR__ . '/templates/record_block.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testSimpleTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_simple_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/simple_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupBlocks()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_group_blocks.xml',
            'template' => 'file://' . __DIR__ . '/templates/group_blocks.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupBlocksTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_group_blocks_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/group_blocks_with_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testGroupedTable()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_grouped_table.xml',
            'template' => 'file://' . __DIR__ . '/templates/grouped_table.docx',
            'filename' => __METHOD__ . '_'
        ));
    }

    public function testDatasources()
    {
        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/definitions/adb_doc_datasources.xml',
            'template' => 'file://' . __DIR__ . '/templates/datasources.docx',
            'filename' => __METHOD__ . '_',
            'exportClass' => 'Addressbook_Export_TestDocDataSource'
        ));
    }

    /**
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testExportLetter()
    {
        // privat
        $contactPrivat = new Addressbook_Model_Contact([
                'n_given' => 'Privat',
                'n_family' => 'Test Preferred',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contactPrivat Addressbook_Model_Contact */
        $contactPrivat = Addressbook_Controller_Contact::getInstance()->create($contactPrivat);

        // business
        $contactBusiness = new Addressbook_Model_Contact([
                'n_given' => 'Business',
                'n_family' => 'Test Preferred',
                'adr_one_street' => 'Business Street 22',
                'adr_one_postalcode' => '1235',
                'adr_one_locality' => 'Business City',
                'preferred_address' => 0
            ]
        );
        /* @var $contactBusiness Addressbook_Model_Contact */
        $contactBusiness = Addressbook_Controller_Contact::getInstance()->create($contactBusiness);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_family', 'operator' => 'equals', 'value' => $contactPrivat->n_family]
        ]);
        $export = new Addressbook_Export_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(new Tinebase_Model_ImportExportDefinitionFilter([
                    'model' => 'Addressbook_Model_Contact',
                    'name' => 'adb_letter_doc'
                ]))->getFirstRecord()->getId()
            ]);

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);
     
        static::assertContains($contactPrivat->n_given, $plain);
        static::assertContains($contactPrivat->adr_two_street, $plain);
        static::assertContains($contactBusiness->n_given, $plain);
        static::assertContains($contactBusiness->adr_one_street, $plain);
    }

    /**
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testExportDetailDoc()
    {
        $contact = new Addressbook_Model_Contact([
                'n_given' => 'Privat',
                'n_family' => 'Test Contact with a relation',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contact Addressbook_Model_Contact */
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);

        $contactRelated = new Addressbook_Model_Contact([
                'n_given' => 'Privat Related',
                'n_family' => 'Test Related',
                'adr_two_street' => 'Privat Street 1',
                'adr_two_postalcode' => '1234',
                'adr_two_locality' => 'Privat City',
                'preferred_address' => 1
            ]
        );
        /* @var $contactRelated Addressbook_Model_Contact */
        $contactRelated = Addressbook_Controller_Contact::getInstance()->create($contactRelated);

        Tinebase_Relations::getInstance()->setRelations(Addressbook_Model_Contact::class, 'Sql', $contact->getId(), [[
            'related_degree' => 'sibling',
            'related_model' => Addressbook_Model_Contact::class,
            'related_backend' => 'Sql',
            'related_id' => $contactRelated->getId(),
            'type' => 'Contact'
        ]]);

        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_family', 'operator' => 'equals', 'value' => $contact->n_family]
        ]);
        
        $export = new Addressbook_Export_Doc($filter, null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(new Tinebase_Model_ImportExportDefinitionFilter([
                    'model' => Addressbook_Model_Contact::class,
                    'format' => 'docx',
                    'label' => 'Word details'
                ]))->getFirstRecord()->getId()
            ]);

        $doc = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->save($doc);

        $plain = $this->getPlainTextFromDocx($doc);

        static::assertContains($contactRelated->getTitle(), $plain);
    }
}
