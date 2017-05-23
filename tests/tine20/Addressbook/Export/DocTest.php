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

    public function testExportLetter()
    {
        static::markTestSkipped('FIX ME');

        $this->_genericExportTest(array(
            'definition' => __DIR__ . '/../../../../tine20/Addressbook/Export/definitions/adb_default_doc.xml',
            'template' => 'file://' . __DIR__ . '/../../../../tine20/Addressbook/Export/templates/FIXME.docx',
            'filename' => __METHOD__ . '_',
        ));
        /*
        // make sure definition is imported
        $definitionFile = __DIR__ . '/../../../../tine20/Addressbook/Export/definitions/adb_default_doc.xml';
        $app = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        Tinebase_ImportExportDefinition::getInstance()->updateOrCreateFromFilename($definitionFile, $app);

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_given', 'operator' => 'in', 'value' => array('James', 'John'))
        ));
        $doc = new Addressbook_Export_Doc($filter);
        $doc->generate();

        $tempfile = tempnam(Tinebase_Core::getTempDir(), __METHOD__ . '_') . '.docx';
        $doc->save($tempfile);

        $this->assertGreaterThan(0, filesize($tempfile));*/
    }
}
