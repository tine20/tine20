<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EFile
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class EFile_EFileNodeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Tinebase_FileSystem::getInstance()->resetBackends();
        Tinebase_Core::clearAppInstanceCache();
        EFile_Controller::registerTreeNodeHooks();
        Tinebase_FileSystem::getInstance()->clearStatCache();
    }

    /**
     * asserts are done in testCreateEFileTree()
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _createEFileTree()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir(Filemanager_Controller_Node::getInstance()->addBasePath('/shared'));
        $basePath = rtrim(Filemanager_Controller_Node::getInstance()->addBasePath(''), '/');

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/A');
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        EFile_Controller::getInstance()->createEFileFolder($path . '1', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);

        $path = $fs->getPathOfNode($node, true, true) . '/B';
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        EFile_Controller::getInstance()->createEFileFolder($path . '1', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        EFile_Controller::getInstance()->createEFileFolder($path . '2', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);

        $path = $fs->getPathOfNode($node, true, true) . '/C';
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_FILE);
        EFile_Controller::getInstance()->createEFileFolder($path . '1', EFile_Model_EFileTierType::TIER_TYPE_FILE);

        $path = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($path . '/D1', EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE);
        EFile_Controller::getInstance()->createEFileFolder($path . '/D11', EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE);

        $pathD1 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $pathD1 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $pathD1 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($path . '/D2', EFile_Model_EFileTierType::TIER_TYPE_CASE);
        EFile_Controller::getInstance()->createEFileFolder($path . '/D21', EFile_Model_EFileTierType::TIER_TYPE_CASE);

        $pathD2 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $pathD2 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $pathD2 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($path . '/D3', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);
        EFile_Controller::getInstance()->createEFileFolder($path . '/D31', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);

        $path1 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($pathD1 . '/E1', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);
        EFile_Controller::getInstance()->createEFileFolder($pathD1 . '/E11', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);

        $path1 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($pathD1 . '/E2', EFile_Model_EFileTierType::TIER_TYPE_CASE);
        EFile_Controller::getInstance()->createEFileFolder($pathD1 . '/E21', EFile_Model_EFileTierType::TIER_TYPE_CASE);

        $path1 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);

        $node = EFile_Controller::getInstance()->createEFileFolder($pathD2 . '/E1', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);
        EFile_Controller::getInstance()->createEFileFolder($pathD2 . '/E11', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR);

        $path1 = $fs->getPathOfNode($node, true, true);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
        Filemanager_Controller_Node::getInstance()->createNodes(str_replace($basePath, '', $path1 . '/a1.doc'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE);
    }

    protected function _assertCreateFolderFail($path, $type)
    {
        static::expectException(Tinebase_Exception_Record_Validation::class);
        EFile_Controller::getInstance()->createEFileFolder($path, $type);
    }

    protected function _assertEFile($path, $type, $expectedNodeName, $expectedRefNum, $expectedToken)
    {
        $node = Tinebase_FileSystem::getInstance()->stat($path);
        static::assertSame($type, $node->{EFile_Config::TREE_NODE_FLD_TIER_TYPE});
        if (EFile_Model_EFileTierType::TIER_TYPE_FILE === $node->{EFile_Config::TREE_NODE_FLD_TIER_TYPE}) {

            $expander = new Tinebase_Record_Expander(Tinebase_Model_Tree_Node::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    EFile_Config::TREE_NODE_FLD_FILE_METADATA => []
                ]
            ]);
            $expander->expand(new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [$node]));

            $this->assertInstanceOf(EFile_Model_FileMetadata::class, $node->{EFile_Config::TREE_NODE_FLD_FILE_METADATA});
            $this->assertInstanceOf(Tinebase_DateTime::class, $node->{EFile_Config::TREE_NODE_FLD_FILE_METADATA}
                ->{EFile_Model_FileMetadata::FLD_DURATION_START});
            if (null === ($contact = Addressbook_Config::getInstallationRepresentative())) {
                $str = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) ?: 'tine20';
            } else {
                $str = $contact->n_fileas;
            }
            $this->assertSame($str, $node->{EFile_Config::TREE_NODE_FLD_FILE_METADATA}
                ->{EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE});
        } else {
            $this->assertNull($node->{EFile_Config::TREE_NODE_FLD_FILE_METADATA}, 'metadata expected null');
        }
        static::assertSame($expectedNodeName, $node->name);
        static::assertSame($expectedRefNum, $node->{EFile_Config::TREE_NODE_FLD_TIER_REF_NUMBER});
        static::assertSame($expectedToken, $node->{EFile_Config::TREE_NODE_FLD_TIER_TOKEN});
    }

    public function testGetParent()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $this->_createEFileTree();
        $filePath = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/01 - A/01 - B/000001 - C');
        $childPath = $filePath . '/001 - D1/#000003 - E2/000001 - a.doc';

        $fmJson = new Filemanager_Frontend_Json();
        $parent = $fmJson->getParentNodeByFilter($fs->stat($childPath)->getId(), [
            ['field' => EFile_Config::TREE_NODE_FLD_TIER_TYPE, 'operator' => 'equals', 'value' => EFile_Model_EFileTierType::TIER_TYPE_FILE]
        ]);

        static::assertSame($fs->stat($filePath)->getId(), $parent['id']);
    }

    public function testRename()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir(Filemanager_Controller_Node::getInstance()->addBasePath('/shared'));

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/A');
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $nodePath = $fs->getPathOfNode($node, true, false);

        $newNodePath = $nodePath . '1';
        $node = $fs->rename($nodePath, $newNodePath);

        static::assertSame($newNodePath, $fs->getPathOfNode($node, true, false));
    }

    public function testRenameFail()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir(Filemanager_Controller_Node::getInstance()->addBasePath('/shared'));

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/A');
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $nodePath = $fs->getPathOfNode($node, true, false);

        $newNodePath = explode('/', $nodePath);
        $newNodePath[count($newNodePath) - 1] = '1' . $newNodePath[count($newNodePath) - 1];
        $newNodePath = join('/', $newNodePath);

        static::expectException(Tinebase_Exception_Record_Validation::class);
        static::expectExceptionMessage('node name needs to start with efile tier token');
        $fs->rename($nodePath, $newNodePath);
    }

    public function testRenameDeniedSubstrings()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir(Filemanager_Controller_Node::getInstance()->addBasePath('/shared'));

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/A');
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $nodePath = $fs->getPathOfNode($node, true, false);

        $newNodePath = $nodePath . '#';

        static::expectException(Tinebase_Exception_SystemGeneric::class);
        $translation = Tinebase_Translation::getTranslation('EFile');
        static::expectExceptionMessage($translation->_('EFile node names may not contain:') . ' ' . '#');
        $fs->rename($nodePath, $newNodePath);
    }

    public function testMove()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $this->_createEFileTree();
        $fileGroupPath = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/01 - A/01 - B');
        $subFilePath = $fileGroupPath . '/000001 - C/001 - D1';
        $newFilePath = $fileGroupPath . '/000003 - D';

        // add some file content, file size, file content type
        file_put_contents('tine20://' . $subFilePath . '/000001 - a.doc', file_get_contents(__DIR__ . '/someFile.docx'));
        $oldDocxFile = $fs->stat($subFilePath . '/000001 - a.doc');

        $fs->rename($subFilePath, $fileGroupPath . '/D');
        $fs->clearStatCache();

        // check new hierachy
        $this->_assertEFile($newFilePath, EFile_Model_EFileTierType::TIER_TYPE_FILE, '000003 - D', '//01.01/000003', '000003');
        $this->_assertEFile($newFilePath . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000003-000001', '000001');
        $this->_assertEFile($newFilePath . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000003-000002', '000002');
        $this->_assertEFile($newFilePath . '/E1', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E1', '//01.01/000003', '');
        $this->_assertEFile($newFilePath . '/E11', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E11', '//01.01/000003', '');

        $this->_assertEFile($newFilePath . '/#000005 - E2', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000005 - E2', '//01.01/000003#000005', '#000005');
        $this->_assertEFile($newFilePath . '/#000006 - E21', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000006 - E21', '//01.01/000003#000006', '#000006');

        $pathE2 = $newFilePath . '/#000005 - E2';
        $this->_assertEFile($pathE2 . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000003#000005-000001', '000001');
        $this->_assertEFile($pathE2 . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000003#000005-000002', '000002');


        $pathE1 = $newFilePath . '/E1';
        $this->_assertEFile($pathE1 . '/000003 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000003 - a.doc', '//01.01/000003-000003', '000003');
        $this->_assertEFile($pathE1 . '/000004 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000004 - a1.doc', '//01.01/000003-000004', '000004');


        // check old paths / links
        $oldSubFileNode = $fs->stat($subFilePath);
        // TODO check modlog (notes)

        $this->_assertEFile($subFilePath, EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE, '001 - D1', '//01.01/000001/001', '001');
        $this->_assertEFile($subFilePath . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000001/001-000001', '000001');
        $this->_assertEFile($subFilePath . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000001/001-000002', '000002');

        $fs->clearStatCache($subFilePath . '/000001 - a.doc');
        $linkNode = $fs->stat($subFilePath . '/000001 - a.doc');
        static::assertSame(Tinebase_Model_Tree_FileObject::TYPE_LINK, $linkNode->type);
        static::assertSame($oldDocxFile->contenttype, $linkNode->contenttype);
        static::assertSame(null, $linkNode->size);
    }

    public function testFMJsonFEUpdateMetadata()
    {
        $this->_createEFileTree();

        $fileGroupPath = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/01 - A/01 - B');
        $filePath = $fileGroupPath . '/000001 - C'; // /000001 - a.doc';

        $fmFE = new Filemanager_Frontend_Json();
        $node = $fmFE->getNode(Tinebase_FileSystem::getInstance()->stat($filePath));

        $savedNode = $fmFE->saveNode($node);
        static::assertArrayHasKey(EFile_Config::TREE_NODE_FLD_FILE_METADATA, $savedNode);
        static::assertArrayHasKey(EFile_Model_FileMetadata::FLD_IS_HYBRID, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA]);
        static::assertSame('0', $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_IS_HYBRID]);
        if (null === ($contact = Addressbook_Config::getInstallationRepresentative())) {
            $str = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST) ?: 'tine20';
        } else {
            $str = $contact->n_fileas;
        }
        static::assertSame($str, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE]);
        static::assertArrayHasKey(EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA]);

        $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY] =
            $this->_personas['sclever']->contact_id;
        $savedNode = $fmFE->saveNode($savedNode);

        static::assertArrayHasKey(EFile_Config::TREE_NODE_FLD_FILE_METADATA, $savedNode);
        static::assertArrayHasKey('id', $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY]);
        static::assertSame($this->_personas['sclever']->contact_id, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY]['id']);
        static::assertArrayHasKey(EFile_Model_FileMetadata::FLD_IS_HYBRID, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA]);
        static::assertSame('0', $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_IS_HYBRID]);
        static::assertSame($str, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA][EFile_Model_FileMetadata::FLD_COMMISSIONED_OFFICE]);
        static::assertArrayHasKey(EFile_Model_FileMetadata::FLD_FINAL_DECREE_BY, $savedNode[EFile_Config::TREE_NODE_FLD_FILE_METADATA]);
    }

    public function testFMJsonFEMoveNodes1()
    {
        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared');
        $fs = Tinebase_FileSystem::getInstance();
        if (!$fs->isDir($path)) {
            $fs->createAclNode($path);
        }

        EFile_Controller::getInstance()->createEFileFolder($path . '/A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $this->_assertEFile($path . '/01 - A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '01 - A', '//01', '01');

        $srcPath = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/FileGroup/File/DocumentDir');
        $fs->mkdir($srcPath);
        file_put_contents('tine20://' . $srcPath . '/dd_file.txt', 'unittestcontent1');
        file_put_contents('tine20://' . dirname($srcPath) . '/f_file.txt', 'unittestcontent2');

        $fmFE = new Filemanager_Frontend_Json();
        $fmFE->moveNodes(['/shared/FileGroup'], ['/shared/01 - A/FileGroup'], false);

        $path .= '/01 - A';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '01 - A', '//01', '01');

        $path .= '/01 - FileGroup';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '01 - FileGroup', '//01.01', '01');

        $path .= '/000001 - File';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_FILE, '000001 - File', '//01.01/000001', '000001');

        $node = $fs->stat($path);
        $children = $fs->searchNodes(new Tinebase_Model_Tree_Node_Filter([['field' => 'parent_id', 'operator' => 'equals', 'value' => $node->getId()]]));

        $this->_assertEFile($path . '/000002 - f_file.txt', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - f_file.txt', '//01.01/000001-000002', '000002');

        $path .= '/DocumentDir';
        $this->_assertEFile($path , EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'DocumentDir', '//01.01/000001', '');

        $this->_assertEFile($path . '/000001 - dd_file.txt', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - dd_file.txt', '//01.01/000001-000001', '000001');
    }

    public function testFMJsonFEMoveNodes2()
    {
        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared');
        $fs = Tinebase_FileSystem::getInstance();
        if (!$fs->isDir($path)) {
            $fs->createAclNode($path);
        }

        EFile_Controller::getInstance()->createEFileFolder($path . '/A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $path .= '/01 - A';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '01 - A', '//01', '01');

        EFile_Controller::getInstance()->createEFileFolder($path . '/FileGroup', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        $path .= '/01 - FileGroup';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '01 - FileGroup', '//01.01', '01');

        EFile_Controller::getInstance()->createEFileFolder($path . '/File', EFile_Model_EFileTierType::TIER_TYPE_FILE);
        $path .= '/000001 - File';
        $this->_assertEFile($path, EFile_Model_EFileTierType::TIER_TYPE_FILE, '000001 - File', '//01.01/000001', '000001');

        $srcPath = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/DocumentDir');
        $fs->mkdir($srcPath);
        file_put_contents('tine20://' . $srcPath . '/dd_file.txt', 'unittestcontent1');

        $fmFE = new Filemanager_Frontend_Json();
        $fmFE->moveNodes(['/shared/DocumentDir'], ['/shared/01 - A/01 - FileGroup/000001 - File/DocumentDir'], false);

        $path .= '/DocumentDir';
        $this->_assertEFile($path , EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'DocumentDir', '//01.01/000001', '');

        $this->_assertEFile($path . '/000001 - dd_file.txt', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - dd_file.txt', '//01.01/000001-000001', '000001');
    }

    public function testOOIcreateNew()
    {
        if (!Tinebase_Application::getInstance()->isInstalled('OnlyOfficeIntegrator')) {
            static::markTestSkipped('needs OOI installed to run');
        }

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared');
        $fs = Tinebase_FileSystem::getInstance();
        if (!$fs->isDir($path)) {
            $fs->createAclNode($path);
        } else {
            $fs->setGrantsForNode($fs->stat($path), Tinebase_Model_Grants::getDefaultGrants([
                Tinebase_Model_Grants::GRANT_DOWNLOAD => true
            ], [
                Tinebase_Model_Grants::GRANT_PUBLISH => true
            ]));
        }

        $path .= '/A';
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        $nodePath = $fs->getPathOfNode($node, true, false);
        $node = EFile_Controller::getInstance()->createEFileFolder($nodePath . '/B', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        $nodePath = $fs->getPathOfNode($node, true, false);
        $node = EFile_Controller::getInstance()->createEFileFolder($nodePath . '/C', EFile_Model_EFileTierType::TIER_TYPE_FILE);
        $nodePath = $fs->getPathOfNode($node, true, false);

        $ooiFE = new OnlyOfficeIntegrator_Frontend_Json();
        $token = $ooiFE->createNew('text', $nodePath, 'Neues Dokument');
    }

    public function testFMJsonFE()
    {
        $this->_createEFileTree();

        $raii = new Tinebase_RAII(function() { Tinebase_Core::set(Tinebase_Core::REQUEST, null); });
        $headerToken = str_replace('_', '-', EFile_Config::TREE_NODE_FLD_TIER_TYPE);
        $request = Tinebase_Http_Request::fromString(
            "OPTIONS /index.php HTTP/1.1\r\n".
            'X-TINE20-REQUEST-CONTEXT-' . $headerToken . ': ' . EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $fmFE = new Filemanager_Frontend_Json();
        $node = $fmFE->createNode('/shared/A2', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);

        static::assertArrayHasKey(EFile_Config::TREE_NODE_FLD_TIER_TYPE, $node);
        static::assertSame(EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, $node[EFile_Config::TREE_NODE_FLD_TIER_TYPE]);

        /**
         * create file without header!
         */
        $request = Tinebase_Http_Request::fromString(
            "OPTIONS /index.php HTTP/1.1\r\n"
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        $node = $fmFE->createNode('/shared/01 - A/01 - B/000001 - C/Z', Tinebase_Model_Tree_FileObject::TYPE_FILE);
        static::assertArrayHasKey(EFile_Config::TREE_NODE_FLD_TIER_TYPE, $node);
        static::assertSame(EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, $node[EFile_Config::TREE_NODE_FLD_TIER_TYPE]);

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared');
        $this->_assertEFile($path . '/03 - A2', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '03 - A2', '//03', '03');
        $this->_assertEFile($path . '/01 - A/01 - B/000001 - C/000005 - Z', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000005 - Z', '//01.01/000001-000005', '000005');

        unset($raii);
    }

    public function testCreateEFileTree($createEFileTree = true)
    {
        if ($createEFileTree) {
            $this->_createEFileTree();
        }
        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared');

        $this->_assertEFile($path . '/01 - A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '01 - A', '//01', '01');
        $this->_assertEFile($path . '/02 - A1', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '02 - A1', '//02', '02');

        $path .= '/01 - A';
        $this->_assertEFile($path . '/01 - B', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '01 - B', '//01.01', '01');
        $this->_assertEFile($path . '/02 - B1', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '02 - B1', '//01.02', '02');
        $this->_assertEFile($path . '/03 - B2', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '03 - B2', '//01.03', '03');

        $path .= '/01 - B';
        $this->_assertEFile($path . '/000001 - C', EFile_Model_EFileTierType::TIER_TYPE_FILE, '000001 - C', '//01.01/000001', '000001');
        $this->_assertEFile($path . '/000002 - C1', EFile_Model_EFileTierType::TIER_TYPE_FILE, '000002 - C1', '//01.01/000002', '000002');

        $path .= '/000001 - C';
        $this->_assertEFile($path . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000001-000001', '000001');
        $this->_assertEFile($path . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000001-000002', '000002');

        $this->_assertEFile($path . '/001 - D1', EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE, '001 - D1', '//01.01/000001/001', '001');
        $this->_assertEFile($path . '/002 - D11', EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE, '002 - D11', '//01.01/000001/002', '002');

        $pathD1 = $path . '/001 - D1';
        $this->_assertEFile($pathD1 . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000001/001-000001', '000001');
        $this->_assertEFile($pathD1 . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000001/001-000002', '000002');

        $this->_assertEFile($path . '/#000001 - D2', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000001 - D2', '//01.01/000001#000001', '#000001');
        $this->_assertEFile($path . '/#000002 - D21', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000002 - D21', '//01.01/000001#000002', '#000002');

        $pathD2 = $path . '/#000001 - D2';
        $this->_assertEFile($pathD2 . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000001#000001-000001', '000001');
        $this->_assertEFile($pathD2 . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000001#000001-000002', '000002');

        $this->_assertEFile($path . '/D3', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'D3', '//01.01/000001', '');
        $this->_assertEFile($path . '/D31', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'D31', '//01.01/000001', '');

        $pathD3 = $path . '/D3';
        $this->_assertEFile($pathD3 . '/000003 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000003 - a.doc', '//01.01/000001-000003', '000003');
        $this->_assertEFile($pathD3 . '/000004 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000004 - a1.doc', '//01.01/000001-000004', '000004');

        $this->_assertEFile($pathD1 . '/E1', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E1', '//01.01/000001/001', '');
        $this->_assertEFile($pathD1 . '/E11', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E11', '//01.01/000001/001', '');

        $pathE1 = $pathD1 . '/E1';
        $this->_assertEFile($pathE1 . '/000003 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000003 - a.doc', '//01.01/000001/001-000003', '000003');
        $this->_assertEFile($pathE1 . '/000004 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000004 - a1.doc', '//01.01/000001/001-000004', '000004');

        $this->_assertEFile($pathD1 . '/#000003 - E2', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000003 - E2', '//01.01/000001/001#000003', '#000003');
        $this->_assertEFile($pathD1 . '/#000004 - E21', EFile_Model_EFileTierType::TIER_TYPE_CASE, '#000004 - E21', '//01.01/000001/001#000004', '#000004');

        $pathE2 = $pathD1 . '/#000003 - E2';
        $this->_assertEFile($pathE2 . '/000001 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000001 - a.doc', '//01.01/000001/001#000003-000001', '000001');
        $this->_assertEFile($pathE2 . '/000002 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000002 - a1.doc', '//01.01/000001/001#000003-000002', '000002');

        $this->_assertEFile($pathD2 . '/E1', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E1', '//01.01/000001#000001', '');
        $this->_assertEFile($pathD2 . '/E11', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'E11', '//01.01/000001#000001', '');

        $pathE1 = $pathD2 . '/E1';
        $this->_assertEFile($pathE1 . '/000003 - a.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000003 - a.doc', '//01.01/000001#000001-000003', '000003');
        $this->_assertEFile($pathE1 . '/000004 - a1.doc', EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, '000004 - a1.doc', '//01.01/000001#000001-000004', '000004');
    }

    public function testCreateMasterPlanTier1Fail()
    {
        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/A');
        Tinebase_FileSystem::getInstance()->mkdir($path);
        $this->_assertCreateFolderFail($path . '/A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
    }

    public function testReplication()
    {
        // prepare test environment
        $oldFsModLog = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE};
        $raii = new Tinebase_RAII(function() use($oldFsModLog) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = $oldFsModLog;
        });
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $filesystem = Tinebase_FileSystem::getInstance();
        $filesystem->resetBackends();
        Tinebase_Core::clearAppInstanceCache();
        EFile_Controller::registerTreeNodeHooks();

        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $this->testCreateEFileTree();

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instanceSeq, 1000);

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $filesystem->clearStatCache();

        $this->assertTrue(Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs($modifications),
            'applying replication modlogs failed');

        $this->testCreateEFileTree(false);

        unset($raii);
    }

    public function testMultiBasePath()
    {
        EFile_Config::getInstance()->{EFile_Config::BASE_PATH} = [
            '/shared/',
            '/shared/multi/'
        ];

        $raii = new Tinebase_RAII(function() {
            EFile_Config::getInstance()->{EFile_Config::BASE_PATH} = ['/shared/'];
        });

        $fs = Tinebase_FileSystem::getInstance();
        $fs->mkdir(Filemanager_Controller_Node::getInstance()->addBasePath('/shared/multi'));

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/multi/A');
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);
        EFile_Controller::getInstance()->createEFileFolder($path . '1', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);

        $path = $fs->getPathOfNode($node, true, true) . '/B';
        $node = EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        EFile_Controller::getInstance()->createEFileFolder($path . '1', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP);
        EFile_Controller::getInstance()->createEFileFolder($path . '2', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN);

        $path = $fs->getPathOfNode($node, true, true) . '/C';
        EFile_Controller::getInstance()->createEFileFolder($path, EFile_Model_EFileTierType::TIER_TYPE_FILE);

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/shared/multi');

        $this->_assertEFile($path . '/01 - A', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '01 - A', '//01', '01');
        $this->_assertEFile($path . '/02 - A1', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '02 - A1', '//02', '02');

        $path .= '/01 - A';
        $this->_assertEFile($path . '/01 - B', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '01 - B', '//01.01', '01');
        $this->_assertEFile($path . '/02 - B1', EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, '02 - B1', '//01.02', '02');
        $this->_assertEFile($path . '/03 - B2', EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, '03 - B2', '//01.03', '03');

        $path .= '/01 - B';
        $this->_assertEFile($path . '/000001 - C', EFile_Model_EFileTierType::TIER_TYPE_FILE, '000001 - C', '//01.01/000001', '000001');

        $this->testCreateEFileTree();

        unset($raii);
    }
}
