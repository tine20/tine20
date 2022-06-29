<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Message\RequestInterface;
use \Firebase\JWT\JWT;

/**
 * Class OnlyOfficeIntegrator_JsonTests
 *
 * @property OnlyOfficeIntegrator_Frontend_Json $_uit
 */
class OnlyOfficeIntegrator_JsonTests extends TestCase
{
    protected $_resetJWTSecret = false;
    protected $_resetOOServerUrl = false;

    /**
     * @var RequestInterface
     */
    protected $_oldRequest = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->_oldRequest = Tinebase_Core::getContainer()->get(RequestInterface::class);

        $this->_uit = new OnlyOfficeIntegrator_Frontend_Json();

        if (empty(OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET})) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET} = 'asdfasfasf';
            $this->_resetJWTSecret = true;
        } else {
            $this->_resetJWTSecret = false;
        }

        if (empty(OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL})) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} = 'http://localhost/OO/CmdSvc';
            $this->_resetOOServerUrl = true;
        } else {
            $this->_resetOOServerUrl = false;
        }
    }

    public function tearDown(): void
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class, $this->_oldRequest);

        if ($this->_resetJWTSecret) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET} = '';
        }
        if ($this->_resetOOServerUrl) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} = '';
        }

        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter(null);

        parent::tearDown();
    }

    public function testGetHistoryDataExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _key needs to be a none empty string');
        $this->_uit->getHistoryData(1, 1);
    }

    public function testGetHistoryDataExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _key needs to be a none empty string');
        $this->_uit->getHistoryData('0', 1);
    }

    public function testGetHistoryDataExceptionParameter3()
    {
        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token not valid');
        static::expectExceptionCode(404);
        $this->_uit->getHistoryData('1', '1');
    }

    public function testGetHistoryDataExceptionParameter4()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _version needs to be an integer greater 0');
        $this->_uit->getHistoryData('1', -1);
    }

    public function testGetHistoryDataExceptionParameter5()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _version needs to be an integer greater 0');
        $this->_uit->getHistoryData('1', '-1');
    }

    public function testGetHistoryDataExceptionParameter6()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _version needs to be an integer greater 0');
        $this->_uit->getHistoryData('1', 'a1');
    }

    public function testGetHistoryData()
    {
        static::markTestSkipped('TODO fix me');

        $history = $this->testGetHistory(false);

        $historyData = $this->_uit->getHistoryData($history['key'], $history['history'][0]['version']);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));
        $ctrl = OnlyOfficeIntegrator_Controller::getInstance();

        static::assertArrayHasKey('changesUrl', $historyData);
        $urlParts = @array_reverse(explode('/', $historyData['changesUrl']));
        $response = $ctrl->getChanges($urlParts[1], $urlParts[0]);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('changesContent', (string)$response->getBody());

        static::assertArrayHasKey('key', $historyData);
        static::assertArrayHasKey('previous', $historyData);
        static::assertArrayHasKey('key', $historyData['previous']);
        static::assertArrayHasKey('url', $historyData['previous']);
        static::assertStringStartsWith(OnlyOfficeIntegrator_Model_AccessToken::getBaseUrl() . '/getDocument/',
            $historyData['previous']['url']);
        $token = @end(explode('/', $historyData['previous']['url']));

        $response = $ctrl->getDocument($token);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blub', (string)$response->getBody());

        static::assertArrayHasKey('url', $historyData);
        static::assertStringStartsWith(OnlyOfficeIntegrator_Model_AccessToken::getBaseUrl() . '/getDocument/',
            $historyData['url']);
        $token = @end(explode('/', $historyData['url']));

        $response = $ctrl->getDocument($token);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blubblub', (string)$response->getBody());

        static::assertArrayHasKey('version', $historyData);
        static::assertSame('1', $historyData['version']);



        $historyData = $this->_uit->getHistoryData($history['key'], $history['history'][1]['version']);

        static::assertArrayHasKey('changesUrl', $historyData);
        $urlParts = @array_reverse(explode('/', $historyData['changesUrl']));
        $response = $ctrl->getChanges($urlParts[1], $urlParts[0]);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('changes1Content', (string)$response->getBody());

        static::assertArrayHasKey('key', $historyData);
        static::assertArrayHasKey('previous', $historyData);
        static::assertArrayHasKey('key', $historyData['previous']);
        static::assertArrayHasKey('url', $historyData['previous']);
        static::assertStringStartsWith(OnlyOfficeIntegrator_Model_AccessToken::getBaseUrl() . '/getDocument/',
            $historyData['previous']['url']);
        $token = @end(explode('/', $historyData['previous']['url']));

        $response = $ctrl->getDocument($token);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blubblub', (string)$response->getBody());

        static::assertArrayHasKey('url', $historyData);
        static::assertStringStartsWith(OnlyOfficeIntegrator_Model_AccessToken::getBaseUrl() . '/getDocument/',
            $historyData['url']);
        $token = @end(explode('/', $historyData['url']));

        $response = $ctrl->getDocument($token);
        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('moreNewDataPls', (string)$response->getBody());

        static::assertArrayHasKey('version', $historyData);
        static::assertSame('2', $historyData['version']);
    }

    public function testGetHistoryExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _key needs to be a none empty string');
        $this->_uit->getHistory(1);
    }

    public function testGetHistoryExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter _key needs to be a none empty string');
        $this->_uit->getHistory('0');
    }

    public function testGetHistoryExceptionParameter3()
    {
        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token not valid');
        static::expectExceptionCode(404);
        $this->_uit->getHistory('1');
    }

    public function testGetHistory($assert = true)
    {
        static::markTestSkipped('TODO fix me');
        
        $editorCfg = $this->testGetEditorConfigForNodeId(false);

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/changes', 'changesContent');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/changes1', 'changes1Content');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.docx', 'blubblub');
        $token = @end(explode('/', $editorCfg['document']['url']));

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 6,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/testUpdated.docx',
            'history' => ['changes' => 'c', 'serverVersion' => 's'],
            'changesurl' => 'tine20:///Tinebase/folders/shared/ootest/changes',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        OnlyOfficeIntegrator_Controller::getInstance()->updateStatus($token);

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.docx', 'moreNewDataPls');
        $reqBody['changesurl'] = 'tine20:///Tinebase/folders/shared/ootest/changes1';
        rewind($fh);
        fwrite($fh, json_encode($reqBody));
        rewind($fh);
        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        OnlyOfficeIntegrator_Controller::getInstance()->updateStatus($token);

        $history = $this->_uit->getHistory($editorCfg['document']['key']);

        if (!$assert) {
            $history['key'] = $editorCfg['document']['key'];
            return $history;
        }

        static::assertArrayHasKey('currentVersion', $history);
        static::assertSame('3', $history['currentVersion']);

        static::assertArrayHasKey('history', $history);
        static::assertArrayHasKey(0, $history['history']);
        static::assertArrayHasKey(1, $history['history']);
        static::assertArrayHasKey('version', $history['history'][0]);
        static::assertArrayHasKey('version', $history['history'][1]);
        static::assertSame('1', $history['history'][0]['version']);
        static::assertSame('2', $history['history'][1]['version']);
    }

    public function testCreateNewExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter type needs to be a none empty string');
        $this->_uit->createNew(1, 'a');
    }

    public function testCreateNewExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter targetPath needs to be a none empty string');
        $this->_uit->createNew('text', 2);
    }

    public function testCreateNewExceptionParameter3()
    {
        Tinebase_FileSystem::getInstance()->unlink(OnlyOfficeIntegrator_Controller::getNewTemplatePath() . '/new.docx');
        static::expectException(Tinebase_Exception_NotFound::class);
        static::expectExceptionMessage('no new template file for type text found');
        $this->_uit->createNew('text', '2');
    }

    public function testCreateNewExceptionParameter35()
    {
        static::expectException(Tinebase_Exception_InvalidArgument::class);
        static::expectExceptionMessage('Invalid type: 1');
        $this->_uit->createNew('text', '/1/2');
    }

    public function testCreateNewExceptionParameter4()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared');
        static::expectException(Tinebase_Exception_NotFound::class);
        static::expectExceptionMessage('target path is not a folder');
        $this->_uit->createNew('text', '/shared/1/2');
    }

    public function testCreateNewExceptionParameter5()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/ootest');
        file_put_contents('tine20:///Filemanager/folders/shared/ootest/test.jpg', 'blub');
        static::expectException(Tinebase_Exception_NotFound::class);
        static::expectExceptionMessage('target path is not a folder');
        $this->_uit->createNew('text', '/shared/ootest/test.jpg');
    }

    public function testCreateNewExceptionParameter6()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/ootest');
        static::expectException(Tinebase_Exception_NotFound::class);
        static::expectExceptionMessage('target path is not a folder');
        $this->_uit->createNew('text', '/shared/ootest/test.jpg');
    }

    public function testCreateNewFileWithCustomName()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $node = $this->_uit->createNew('text', '/shared/ootest/', 'customFile');

        static::assertArrayHasKey('name', $node, print_r($node, true));
        static::assertSame('customFile.docx', $node['name']);
        static::assertArrayHasKey('id', $node);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node['id'], true)));
    }



    public function testCreateNewDir()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $node = $this->_uit->createNew('text', '/shared/ootest/');

        static::assertArrayHasKey('name', $node);
        $translation = Tinebase_Translation::getTranslation(OnlyOfficeIntegrator_Config::APP_NAME);
        $newName = $translation->_('New Document');
        static::assertSame($newName . '.docx', $node['name']);
        static::assertArrayHasKey('id', $node);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node['id'], true)));

        $node1 = $this->_uit->createNew('text', '/shared/ootest/');
        static::assertArrayHasKey('name', $node1);
        $newName = sprintf($translation->_('New Document (%1$d)'), 1);
        static::assertSame($newName . '.docx', $node1['name']);
        static::assertArrayHasKey('id', $node1);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node1['id'], true)));
    }

    public function testCreateNewTempFile()
    {
        $tmpFile = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_TEMPFILE);
        static::assertArrayHasKey('name', $tmpFile);
        $_ = Tinebase_Translation::getTranslation(OnlyOfficeIntegrator_Config::APP_NAME, Tinebase_Core::getLocale());
        static::assertSame($_->_('New Document') . '.docx', $tmpFile['name']);
        static::assertArrayHasKey('path', $tmpFile);
        static::assertTrue(is_file($tmpFile['path']));
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents($tmpFile['path']));
        static::assertArrayHasKey('type', $tmpFile);
        static::assertSame(mime_content_type($tmpFile['path']), $tmpFile['type']);
    }

    public function testCreateNewTempFileWithCustomName()
    {
        $tmpFile = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_TEMPFILE, 'customFile');
        static::assertArrayHasKey('name', $tmpFile);
        static::assertSame('customFile.docx', $tmpFile['name']);
        static::assertArrayHasKey('path', $tmpFile);
        static::assertTrue(is_file($tmpFile['path']));
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents($tmpFile['path']));
        static::assertArrayHasKey('type', $tmpFile);
        static::assertSame(mime_content_type($tmpFile['path']), $tmpFile['type']);
    }

    public function testCreateNewPersonal()
    {
        $node = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_PERSONAL);
        static::assertArrayHasKey('name', $node);
        $translation = Tinebase_Translation::getTranslation(OnlyOfficeIntegrator_Config::APP_NAME);
        $newName = $translation->_('New Document');
        static::assertSame($newName . '.docx', $node['name']);
        static::assertArrayHasKey('id', $node);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node['id'], true)));

        $node1 = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_PERSONAL);
        static::assertArrayHasKey('name', $node1);
        $newName = sprintf($translation->_('New Document (%1$d)'), 1);
        static::assertSame($newName . '.docx', $node1['name']);
        static::assertArrayHasKey('id', $node1);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node1['id'], true)));
    }

    public function testCreateNewPersonalWithCustomName()
    {
        $node = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_PERSONAL, 'customFile');
        static::assertArrayHasKey('name', $node);
        static::assertSame('customFile.docx', $node['name']);
        static::assertArrayHasKey('id', $node);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node['id'], true)));

        $node1 = $this->_uit->createNew('text', OnlyOfficeIntegrator_Frontend_Json::PATH_TYPE_PERSONAL, 'customFile');
        static::assertArrayHasKey('name', $node1);
        $newName = sprintf('customFile (%1$d)', 1);
        static::assertSame($newName . '.docx', $node1['name']);
        static::assertArrayHasKey('id', $node1);
        static::assertSame(file_get_contents('tine20://' . OnlyOfficeIntegrator_Controller::getNewTemplatePath() .
            '/new.docx'), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()
                ->getPathOfNode($node1['id'], true)));
    }

    public function testExportAsExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter ooUrl needs to be a none empty string');
        $this->_uit->exportAs(1, 'a');
    }

    public function testExportAsExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter targetPath needs to be a none empty string');
        $this->_uit->exportAs('2', 2);
    }

    public function testExportAsExceptionParameter3()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/2');
        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('you don\'t have permissions to create a file there');
        $this->_uit->exportAs('2', '/shared/2/1');
    }

    public function testExportAsExceptionParameter4()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared');
        static::expectException(Tinebase_Exception_NotFound::class);
        static::expectExceptionMessage('child: 1 not found!');
        $this->_uit->exportAs('2', '/shared/1/2');
    }

    public function testExportAsExceptionParameter5()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/tmp');
        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('you don\'t have permissions to create a file there');
        $this->_uit->exportAs('2', '/shared/tmp/test.jpg');
    }

    public function testExportAsExceptionParameter6()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        file_put_contents('tine20:///Filemanager/folders/shared/ootest/test.jpg', 'blub');

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('file already exists');
        $this->_uit->exportAs('tine20:///Filemanager/folders/shared/ootest/test.jpg', '/shared/ootest/test.jpg');
    }

    public function testExportAsExceptionParameter7()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');

        static::expectException(Tinebase_Exception_Backend::class);
        static::expectExceptionMessage('could not open document to export');
        $this->_uit->exportAs('tine20:///Filemanager/folders/shared/ootest/test.jpg', '/shared/ootest/test.jpg');
    }

    public function testExportAs()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        file_put_contents('tine20:///Filemanager/folders/shared/ootest/test.jpg', 'blub');

        $result = $this->_uit->exportAs('tine20:///Filemanager/folders/shared/ootest/test.jpg', '/shared/ootest/x.jpg');
        static::assertArrayHasKey('name', $result);
        static::assertSame('x.jpg', $result['name']);
        static::assertSame('blub', file_get_contents('tine20:///Filemanager/folders/shared/ootest/x.jpg'));
    }

    public function testSaveAsExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be an none empty string');
        $this->_uit->saveAs(1, 'copy', 'a');
    }

    public function testSaveAsExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter copyOrMove needs to be either "copy" or "move"');
        $this->_uit->saveAs('2', 'cpy', 'a');
    }

    public function testSaveAsExceptionParameter3()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter targetPath needs to be an none empty string');
        $this->_uit->saveAs('2', 'copy', 2);
    }

    public function testSaveAsExceptionParameter4()
    {
        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token not found');
        static::expectExceptionCode(404);
        $this->_uit->saveAs('2', 'copy', '/shared/2');
    }

    public function testSaveAsExceptionParameter5()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/2');
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        file_put_contents('tine20:///Filemanager/folders/shared/ootest/test.jpg', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Filemanager/folders/shared/ootest/test.jpg');
        static::assertArrayHasKey('url', $this->_uit->getEmbedUrlForNodeId($node->getId()));
        $key = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
            ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
            $node->getId()],
            ]))->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY};

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('you do not have grants to save');
        $this->_uit->saveAs($key, 'copy', '/shared/2/1');
    }

    public function testSaveAsExceptionParameter6()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Filemanager/folders/shared/2');
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        file_put_contents('tine20:///Filemanager/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Filemanager/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('you don\'t have permissions to create a file there');
        $this->_uit->saveAs($key, 'copy', '/shared/2/nothere.txt');
    }

    public function testSaveAsExceptionParameter7()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $key = $this->testGetEditorConfigForTempFile(false)['document']['key'];

        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('can\'t move a tempFile');
        $this->_uit->saveAs($key, 'move', '/shared/ootest/test.txt');
    }

    public function testSaveAsTempFileOOCmdSvcFailure1()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $key = $this->testGetEditorConfigForTempFile(false)['document']['key'];

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(500, [], '{"error":1}'));
        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        static::expectException(Tinebase_Exception_Backend::class);
        static::expectExceptionMessage('onlyoffice command service did not responde with status code 200');
        $this->_uit->saveAs($key, 'copy', '/shared/ootest/test.txt');
    }

    public function testSaveAsTempFileOOCmdSvcFailure2()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $key = $this->testGetEditorConfigForTempFile(false)['document']['key'];

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":1}'));
        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        static::expectException(Tinebase_Exception_Backend::class);
        static::expectExceptionMessage('only office cmd service failed');
        $this->_uit->saveAs($key, 'copy', '/shared/ootest/test.txt');
    }

    public function testWaitForDocumentSaveExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be string and timeout numeric in range 1 - 600');
        $this->_uit->waitForDocumentSave(1, 5);
    }

    public function testWaitForDocumentSaveExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be string and timeout numeric in range 1 - 600');
        $this->_uit->waitForDocumentSave('a', '5a');
    }

    public function testWaitForDocumentSaveExceptionParameter3()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be string and timeout numeric in range 1 - 600');
        $this->_uit->waitForDocumentSave('a', 0);
    }

    public function testWaitForDocumentSaveExceptionParameter4()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be string and timeout numeric in range 1 - 600');
        $this->_uit->waitForDocumentSave('a', 601);
    }

    public function testWaitForDocumentFail()
    {
        $start = time();
        static::assertFalse($this->_uit->waitForDocumentSave('a', 5), 'expected to fail');
        static::assertLessThan(3, time() - $start, 'expect not to wait');
    }

    public function testWaitForDocumentFail1()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];

        $start = time();
        static::assertFalse($this->_uit->waitForDocumentSave($key, 5), 'expected to fail');
        static::assertLessThan(3, time() - $start, 'expect not to wait');
    }

    public function testWaitForDocument()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];
        static::assertTrue($this->_uit->tokenSignOut($key), 'token sign out did not work');

        $start = time();
        static::assertFalse($this->_uit->waitForDocumentSave($key, 2), 'expected to fail');
        static::assertGreaterThanOrEqual(1, time() - $start, 'expect to wait');
    }

    public function testWaitForDocumentSuccess()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];

        static::assertNotNull($tokenRec = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]))->getFirstRecord(), 'token not found');
        $tokenRec->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION} = 2;
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($tokenRec);

        static::assertTrue($this->_uit->tokenSignOut($key), 'token sign out did not work');

        $start = time();
        static::assertTrue(is_array($this->_uit->waitForDocumentSave($key, 5)), 'expected to succeed');
        static::assertLessThan(3, time() - $start, 'expect not to wait');
    }

    public function testTokenSignOutParameterException()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter key needs to be a none empty string');
        $this->_uit->tokenSignOut(0);
    }

    public function testTokenSignOutBadKey()
    {
        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('key not found');
        static::expectExceptionCode(404);
        $this->_uit->tokenSignOut('a');
    }

    public function testTokenSignOutWithTwoTokensPresent()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' => $key],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        $secondToken = clone $token;
        $secondToken->setId(null);
        $secondToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID} = Tinebase_Record_Abstract::generateUID();
        $secondToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->create($secondToken);

        static::assertTrue($this->_uit->tokenSignOut($key), 'tokenSignOut failed');
        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->get($token->getId());
        static::assertTrue(!!$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED},
            'expect token to be invalidated');
        $secondToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->get($secondToken->getId());
        static::assertTrue(!$secondToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED},
            'expect second token to be not deleted');
        // keep alive should return true
        static::assertSame([$key => true], $this->_uit->tokenKeepAlive([$key]));
        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->get($token->getId());
        static::assertTrue(!$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED},
            'expect token to be reactivated');
    }

    public function testTokenKeepAliveTimeout()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $editorCfg = $this->_uit->getEditorConfigForNodeId($node->getId());
        $key = $editorCfg['document']['key'];
        $token = @end(explode('/', $editorCfg['document']['url']));

        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME,
            [
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN => (string)(Tinebase_DateTime::now()->subDay(2)),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED => 1,
            ],
            OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN . ' = "' . $token . '"');

        static::assertSame([$key => false], $this->_uit->tokenKeepAlive([$key]));
        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' => $key],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals',
                    'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertTrue(!!$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED},
            'expect token to be invalidated');
    }

    public function testTokenSignOut()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $key = $this->_uit->getEditorConfigForNodeId($node->getId())['document']['key'];

        static::assertTrue($this->_uit->tokenSignOut($key), 'tokenSignOut failed');
        static::assertSame([$key => true], $this->_uit->tokenKeepAlive([$key]));
    }

    public function testTokenKeepAliveParameterException()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter keys needs to be an array of tokens');
        $this->_uit->tokenKeepAlive(['', 0, null]);
    }

    public function testTokenKeepAlive()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.jpg', 'blub');
        $node1 = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.jpg');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node2 = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test2.txt', 'blub');
        $node3 = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test2.txt');

        static::assertArrayHasKey('url', $this->_uit->getEmbedUrlForNodeId($node1->getId()));
        $key1 = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node1->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord()
            ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY};
        $key2 = $this->_uit->getEditorConfigForNodeId($node2->getId())['document']['key'];
        $key3 = $this->_uit->getEditorConfigForNodeId($node3->getId())['document']['key'];
        $token3Rec = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node3->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertSame($key3, $token3Rec->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY});
        $token3Rec->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN} = Tinebase_DateTime::now()->subDay(2);
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($token3Rec);

        $result = $this->_uit->tokenKeepAlive(['', 0, null, $key1, $key2, $key3]);
        static::assertSame([$key1 => true, $key2 => true, $key3 => false], $result);
    }

    public function testGetEmbedUrlForNodeId($assert = true)
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.jpg', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.jpg');

        $result = $this->_uit->getEmbedUrlForNodeId($node->getId());
        if (!$assert) {
            return $result;
        }

        Tinebase_Core::set(Tinebase_Core::SESSIONID, Tinebase_Record_Abstract::generateUID());
        $start = time();
        $result = $this->_uit->getEmbedUrlForNodeId($node->getId());
        static::assertLessThan(5, time() - $start, 'expect no wait on second token');

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]));
        static::assertSame(2, $token->count(), 'unexcpected token count');
        static::assertSame($token->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN},
            $token->getLastRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN},
            'same token should be created');
        $token = $token->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, @end(explode('/', $result['url'])));
        static::assertNotNull($token, 'did not find token');
        static::assertSame(OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY, (int)$token
            ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_MODE}, 'wrong mode');
        static::assertSame(OnlyOfficeIntegrator_Model_AccessToken::GRANT_READ, (int)$token
            ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS}, 'wrong grants');

        $msg = print_r($result, true);
        static::assertArrayHasKey('url', $result, $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/getDocument/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $result['url'], $msg);
    }

    public function testGetEmbedUrlForNodeId2()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.jpg', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.jpg');

        $this->_uit->getEmbedUrlForNodeId($node->getId());
        $this->_uit->tokenSignOut(OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]))->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY});

        $start = time();
        $result = $this->_uit->getEmbedUrlForNodeId($node->getId());
        static::assertLessThan(5, time() - $start, 'expect no wait on second token');

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]));
        static::assertSame(1, $token->count(), 'unexcpected token count');
        $token = $token->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, @end(explode('/', $result['url'])));
        static::assertNotNull($token, 'did not find token');

        $msg = print_r($result, true);
        static::assertArrayHasKey('url', $result, $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/getDocument/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $result['url'], $msg);
    }

    public function testGetEmbedUrlForNodeIdExceptionParameter1()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter nodeId needs to be string and revision either null or string');
        $this->_uit->getEmbedUrlForNodeId(null);
    }

    public function testGetEmbedUrlForNodeIdExceptionParameter2()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter nodeId needs to be string and revision either null or string');
        $this->_uit->getEmbedUrlForNodeId(1);
    }

    public function testGetEmbedUrlForNodeIdExceptionParameter3()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter nodeId needs to be string and revision either null or string');
        $this->_uit->getEmbedUrlForNodeId('');
    }

    public function testGetEmbedUrlForNodeIdExceptionParameter4()
    {
        static::expectException(Tinebase_Exception_UnexpectedValue::class);
        static::expectExceptionMessage('parameter nodeId needs to be string and revision either null or string');
        $this->_uit->getEmbedUrlForNodeId('asfd', 1);
    }

    public function testGetTokenForNodeIdException1()
    {
        static::expectException(Tinebase_Exception_NotFound::class);
        $this->_uit->getEmbedUrlForNodeId('asfd');
    }

    public function testGetTokenForNodeIdException2()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.jpg', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.jpg');

        static::expectException(Tinebase_Exception_NotFound::class);
        $this->_uit->getEmbedUrlForNodeId($node->getId(), '321412');
    }

    public function testGetTokenForNodeIdException3()
    {
        Tinebase_FileSystem::getInstance()->mkdir('/Tinebase/folders/shared/ootest/');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.jpg', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.jpg');

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('you do not have access to node ' . $node->getId());
        $this->_uit->getEmbedUrlForNodeId($node->getId());
    }

    public function testGetTokenForNodeIdException4()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.asd', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.asd');

        static::expectException(Tinebase_Exception_SystemGeneric::class);
        static::expectExceptionMessage('filetype of node is not supported');
        $this->_uit->getEmbedUrlForNodeId($node->getId());
    }

    public function testGetTokenForNodeIdException5()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest');

        static::expectException(Tinebase_Exception_SystemGeneric::class);
        static::expectExceptionMessage('filetype of node is not supported');
        $this->_uit->getEmbedUrlForNodeId($node->getId());
    }

    public function testGetTokenForNodeIdException6()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.asd', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.asd');

        static::expectException(Tinebase_Exception_SystemGeneric::class);
        static::expectExceptionMessage('filetype of node is not supported');
        $this->_uit->getEditorConfigForNodeId($node->getId());
    }

    public function testGetTokenForNodeIdException7()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('filesystem modlog needs to be active for this test');
        }

        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $this->_uit->getEditorConfigForNodeId($node->getId());
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'bla');
        
        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":0}'));
        onlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);
        
        static::expectException(Tinebase_Exception_SystemGeneric::class);
        static::expectExceptionMessage('this revision currently can\'t be opened as a different revision is already open');
        static::expectExceptionCode(647);
        $this->_uit->getEditorConfigForNodeId($node->getId());
    }

    public function testGetTokenForNodeId1()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('filesystem modlog needs to be active for this test');
        }

        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        $this->_uit->getEditorConfigForNodeId($node->getId());
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'bla');

        $oldToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        
        sleep(1);
        
        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":1}'));
        onlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        $this->_uit->getEditorConfigForNodeId($node->getId());

        $updatedToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        static::assertNotEquals($oldToken, $updatedToken, 'token should be updated');
    }
    
    public function testGetEditorConfigForTempFile($assert = true)
    {
        $tempFile = Tinebase_TempFile::getInstance()->createTempFile(Tinebase_TempFile::getTempPath(), 'test.txt');
        file_put_contents($tempFile->path, 'blub');

        $editorConfigArray = $this->_uit->getEditorConfigForTempFileId($tempFile->getId());
        if (!$assert) {
            return $editorConfigArray;
        }

        $editorConfig = json_decode(json_encode(JWT::decode($editorConfigArray['token'],
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, ['HS256'])), true);
        unset($editorConfigArray['token']);
        static::assertSame($editorConfig, $editorConfigArray);

        $msg = print_r($editorConfig, true);
        static::assertArrayHasKey('document', $editorConfig, $msg);
        static::assertArrayHasKey('fileType', $editorConfig['document'], $msg);
        static::assertSame('txt', $editorConfig['document']['fileType']);

        static::assertArrayHasKey('documentType', $editorConfig, $msg);
        static::assertSame('text', $editorConfig['documentType']);

        static::assertArrayHasKey('title', $editorConfig['document'], $msg);
        static::assertSame($tempFile->name, $editorConfig['document']['title']);

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $tempFile->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($token, 'did not find access token in db');
        static::assertSame((string)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION,
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION});
        static::assertArrayHasKey('url', $editorConfig['document'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/getDocument/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['url']);

        static::assertArrayHasKey('key', $editorConfig['document'], $msg);
        static::assertSame(Tinebase_Core::getTinebaseId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            $tempFile->getId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR
            . $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['key']);

        static::assertArrayHasKey('permissions', $editorConfig['document'], $msg);
        static::assertArrayHasKey('download', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['download'], $msg);

        static::assertArrayHasKey('edit', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['edit'], $msg);

        static::assertArrayHasKey('print', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['print'], $msg);

        static::assertArrayHasKey('editorConfig', $editorConfig, $msg);
        static::assertArrayHasKey('mode', $editorConfig['editorConfig'], $msg);
        static::assertSame('edit', $editorConfig['editorConfig']['mode']);

        static::assertArrayHasKey('callbackUrl', $editorConfig['editorConfig'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/updateStatus/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['editorConfig']['callbackUrl']);

        static::assertArrayHasKey('user', $editorConfig['editorConfig'], $msg);
        static::assertArrayHasKey('id', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->getId(), $editorConfig['editorConfig']['user']['id']);

        static::assertArrayHasKey('name', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->accountDisplayName, $editorConfig['editorConfig']['user']['name']);

        return $editorConfigArray;
    }

    public function testGetEditorConfigForTempFileWaitForResolution()
    {
        $config = $this->testGetEditorConfigForTempFile(false);
        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' =>
                    $config['document']['key']],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        static::assertTrue($this->_uit->tokenSignOut($config['document']['key']), 'tokenSignOut failed');

        $refl = new ReflectionMethod(OnlyOfficeIntegrator_Frontend_Json::class, 'waitForTokenRequired');
        $refl->setAccessible(true);
        static::assertTrue($refl->invoke($this->_uit, null, $config['document']['key']));

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":0}'));
        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        $start = time();
        try {
            $this->_uit->getEditorConfigForTempFileId($token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
        } catch (OnlyOfficeIntegrator_Exception_WaitForOOSave $e) {
            static::assertGreaterThan(5, time() - $start, 'expected to wait a few secs here');
            return;
        }
        static::fail('expect ' . OnlyOfficeIntegrator_Exception_WaitForOOSave::class . ' to be thrown');
    }

    public function testGetEditorConfigForAttachment($assert = true)
    {
        $contact = Addressbook_Controller_Contact::getInstance()->get(Tinebase_Core::getUser()->contact_id);
        $path = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($contact, true);
        file_put_contents('tine20://' . $path . '/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat($path . '/test.txt');

        $editorConfigArray = $this->_uit->getEditorConfigForAttachment(Addressbook_Model_Contact::class,
            $contact->getId(), $node->getId());

        if (!$assert) {
            return $editorConfigArray;
        }

        $editorConfig = json_decode(json_encode(JWT::decode($editorConfigArray['token'],
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, ['HS256'])), true);
        unset($editorConfigArray['token']);
        static::assertSame($editorConfig, $editorConfigArray);

        $msg = print_r($editorConfig, true);
        static::assertArrayHasKey('document', $editorConfig, $msg);
        static::assertArrayHasKey('fileType', $editorConfig['document'], $msg);
        static::assertSame('txt', $editorConfig['document']['fileType']);

        static::assertArrayHasKey('documentType', $editorConfig, $msg);
        static::assertSame('text', $editorConfig['documentType']);

        static::assertArrayHasKey('title', $editorConfig['document'], $msg);
        static::assertSame($node->name, $editorConfig['document']['title']);

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($token, 'did not find access token in db');
        static::assertArrayHasKey('url', $editorConfig['document'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/getDocument/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['url']);

        static::assertArrayHasKey('key', $editorConfig['document'], $msg);
        static::assertSame(Tinebase_Core::getTinebaseId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            $node->getId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR . '1' .
            OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['key']);

        static::assertArrayHasKey('permissions', $editorConfig['document'], $msg);
        static::assertArrayHasKey('download', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['download'], $msg);

        static::assertArrayHasKey('edit', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['edit'], $msg);

        static::assertArrayHasKey('print', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['print'], $msg);

        static::assertArrayHasKey('editorConfig', $editorConfig, $msg);
        static::assertArrayHasKey('mode', $editorConfig['editorConfig'], $msg);
        static::assertSame('edit', $editorConfig['editorConfig']['mode']);

        static::assertArrayHasKey('callbackUrl', $editorConfig['editorConfig'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/updateStatus/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['editorConfig']['callbackUrl']);

        static::assertArrayHasKey('user', $editorConfig['editorConfig'], $msg);
        static::assertArrayHasKey('id', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->getId(), $editorConfig['editorConfig']['user']['id']);

        static::assertArrayHasKey('name', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->accountDisplayName, $editorConfig['editorConfig']['user']['name']);

        return $editorConfigArray;
    }

    /**
     * @param bool $assert
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testGetEditorConfigForNodeId($assert = true)
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Tinebase/folders/shared/ootest');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'blub');
        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');

        $editorConfigArray = $this->_uit->getEditorConfigForNodeId($node->getId(), $node->revision);

        if (!$assert) {
            return $editorConfigArray;
        }

        $editorConfig = json_decode(json_encode(JWT::decode($editorConfigArray['token'],
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, ['HS256'])), true);
        unset($editorConfigArray['token']);
        static::assertSame($editorConfig, $editorConfigArray);

        $msg = print_r($editorConfig, true);
        static::assertArrayHasKey('document', $editorConfig, $msg);
        static::assertArrayHasKey('fileType', $editorConfig['document'], $msg);
        static::assertSame('txt', $editorConfig['document']['fileType']);

        static::assertArrayHasKey('documentType', $editorConfig, $msg);
        static::assertSame('text', $editorConfig['documentType']);

        static::assertArrayHasKey('title', $editorConfig['document'], $msg);
        static::assertSame($node->name, $editorConfig['document']['title']);

        $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals', 'value' =>
                    $node->getId()],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($token, 'did not find access token in db');
        static::assertArrayHasKey('url', $editorConfig['document'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/getDocument/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['url']);

        static::assertArrayHasKey('key', $editorConfig['document'], $msg);
        static::assertSame(Tinebase_Core::getTinebaseId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            $node->getId() . OnlyOfficeIntegrator_Controller::KEY_SEPARATOR . '1' .
            OnlyOfficeIntegrator_Controller::KEY_SEPARATOR .
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['document']['key']);

        static::assertArrayHasKey('permissions', $editorConfig['document'], $msg);
        static::assertArrayHasKey('download', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['download'], $msg);

        static::assertArrayHasKey('edit', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['edit'], $msg);

        static::assertArrayHasKey('print', $editorConfig['document']['permissions'], $msg);
        static::assertSame(true, $editorConfig['document']['permissions']['print'], $msg);

        static::assertArrayHasKey('editorConfig', $editorConfig, $msg);
        static::assertArrayHasKey('mode', $editorConfig['editorConfig'], $msg);
        static::assertSame('edit', $editorConfig['editorConfig']['mode']);

        static::assertArrayHasKey('callbackUrl', $editorConfig['editorConfig'], $msg);
        static::assertStringContainsString(OnlyOfficeIntegrator_Config::APP_NAME . '/updateStatus/' . $token
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN}, $editorConfig['editorConfig']['callbackUrl']);

        static::assertArrayHasKey('user', $editorConfig['editorConfig'], $msg);
        static::assertArrayHasKey('id', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->getId(), $editorConfig['editorConfig']['user']['id']);

        static::assertArrayHasKey('name', $editorConfig['editorConfig']['user'], $msg);
        static::assertSame(Tinebase_Core::getUser()->accountDisplayName, $editorConfig['editorConfig']['user']['name']);

        return $editorConfigArray;
    }
}
