<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Fulltext....
 */
class Tinebase_FullTextTest extends TestCase
{

    public function setUp(): void
{
        $fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT);
        $javaBin = $fulltextConfig->{Tinebase_Config::FULLTEXT_JAVABIN};
        $tikaJar = $fulltextConfig->{Tinebase_Config::FULLTEXT_TIKAJAR};

        if (!$javaBin || !is_executable($javaBin) || !$tikaJar || !is_file($tikaJar)) {
            static::markTestSkipped('tika needs to be configured for FullText tests');
        }

        parent::setUp();
    }

    public function testFullTextFileIndexing()
    {
        $dirIterator = new DirectoryIterator(__DIR__ . '/files/fulltext');
        $indexer = Tinebase_Fulltext_Indexer::getInstance();
        $textExtract = Tinebase_Fulltext_TextExtract::getInstance();
        $backend = new Tinebase_Backend_Sql([
            'modelName' => 'Foo',
            'tableName' => 'external_fulltext'
        ]);
        $db = Tinebase_Core::getDb();

        /** @var DirectoryIterator $dir */
        foreach ($dirIterator as $dir) {
            if (!$dir->isFile()) {
                continue;
            }
            $fileObject = new Tinebase_Model_Tree_FileObjectFullTextMock([
                'hash' => $dir->getPathname(),
                'type' => Tinebase_Model_Tree_FileObject::TYPE_FILE
            ], true);

            $fileName = $dir->getFilename();
            $tmpFile = null;
            try {
                $tmpFile = $textExtract->fileObjectToTempFile($fileObject);
                static::assertTrue(false !== $tmpFile && is_file($tmpFile), 'failed extracting file: ' . $fileName);
                $indexer->addFileContentsToIndex($fileName, $tmpFile);

                Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
                $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

                $select = $db->select()->from(['external_fulltext' => SQL_TABLE_PREFIX . 'external_fulltext']);
                $filter = new Tinebase_Model_Filter_ExternalFullText([
                    'field' => 'id',
                    'operator' => 'equals',
                    'value' => str_replace('.', '', $fileName),
                    'options' => ['idProperty' => 'id']
                ]);
                $filter->appendFilterSql($select, $backend);
                $data = $select->query()->fetchAll();
                static::assertEquals(in_array($fileName, [
                    'test.jpg',
                    'test.tiff',
                    'test.png'
                ]) ? 0 : 1, count($data), 'full text search failed: ' . $fileName);

            } finally {
                if (null !== $tmpFile) {
                    @unlink($tmpFile);
                }
                $indexer->removeFileContentsFromIndex($fileName);
            }
        }
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
    }
}
