<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EFile
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class EFile_CsvImportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Tinebase_FileSystem::getInstance()->resetBackends();
        Tinebase_Core::clearAppInstanceCache();
        EFile_Controller::registerTreeNodeHooks();
        Tinebase_FileSystem::getInstance()->clearStatCache();
    }

    public function testCsvImport(): void
    {
        $importer = new EFile_Import_Csv();
        $importer->importFile(__DIR__ . '/files/import.csv');

        Tinebase_FileSystem::getInstance()->clearStatCache();

        $basePath = Filemanager_Controller_Node::getInstance()
            ->addBasePath(EFile_Config::getInstance()->{EFile_Config::BASE_PATH}[0]);

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde');
        $this->assertSame('//00', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte');
        $this->assertSame('//00.00', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/00 - Gründung und Umschreibung der Pfarrei');
        $this->assertSame('//00.00.00', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/01 - Allgemeine Pfarr- und Ortsgeschichte');
        $this->assertSame('//00.00.01', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/01 - Allgemeine Pfarr- und Ortsgeschichte/01 - amtlich verfasste Chronik');
        $this->assertSame('//00.00.01.01', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/02 - Pfarrchronik');
        $this->assertSame('//00.00.02', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/02 - Pfarrchronik/01 - Chronik0');
        $this->assertSame('//00.00.02.01', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});

        $node = Tinebase_FileSystem::getInstance()->stat($basePath . '00 - Kirchengemeinde/00 - Pfarr- und Ortgeschichte/02 - Pfarrchronik/02 - Chronik1');
        $this->assertSame('//00.00.02.02', $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});
    }
}