<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */



class OnlyOfficeIntegrator_FilemanagerTests extends TestCase
{
    /**
     * @var OnlyOfficeIntegrator_JsonTests|null
     */
    protected $_jsonTest = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->_jsonTest = new OnlyOfficeIntegrator_JsonTests();
        $this->_jsonTest->setUp();
    }

    public function tearDown(): void
    {
        $this->_jsonTest->tearDown();

        parent::tearDown();
    }

    public function testFileNodesCFsEmpty()
    {
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->clearUnresolvedTokensCache();

        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');

        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        static::assertFalse($node->{OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME});
        static::assertEmpty($node->{OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME});

        $fmJson = new Filemanager_Frontend_Json();
        $fmNode = $fmJson->getNode($node->getId());
        static::assertFalse($fmNode[OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME]);
        static::assertArrayNotHasKey(OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME, $fmNode);
    }

    public function testFileNodeCFs()
    {
        $this->_jsonTest->testGetEditorConfigForNodeId(false);
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->clearUnresolvedTokensCache();

        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        static::assertTrue($node->{OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME});
        $fmJson = new Filemanager_Frontend_Json();
        $fmNode = $fmJson->getNode($node->getId());
        static::assertTrue($fmNode[OnlyOfficeIntegrator_Config::FM_NODE_EDITING_CFNAME]);
        static::assertCount(1, $fmNode[OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME]);
        static::assertSame(Tinebase_Core::getUser()->contact_id, $fmNode[OnlyOfficeIntegrator_Config::FM_NODE_EDITORS_CFNAME][0]['id']);
    }
}
