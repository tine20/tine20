<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

use \Psr\Http\Message\RequestInterface;
use \Firebase\JWT\JWT;


class OnlyOfficeIntegrator_ControllerTests extends TestCase
{
    /**
     * @var OnlyOfficeIntegrator_Controller
     */
    protected $_uit = null;

    /**
     * @var RequestInterface
     */
    protected $_oldRequest = null;

    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_originalTestUser = null;

    protected $_resetJWTSecret = false;

    /**
     * @var OnlyOfficeIntegrator_JsonTests|null
     */
    protected $_jsonTest = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->_oldRequest = Tinebase_Core::getContainer()->get(RequestInterface::class);

        $this->_uit = OnlyOfficeIntegrator_Controller::getInstance();

        $this->_jsonTest = new OnlyOfficeIntegrator_JsonTests();
        $this->_jsonTest->setUp();

        if (empty(OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET})) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET} = 'asdfasfasf';
            $this->_resetJWTSecret = true;
        } else {
            $this->_resetJWTSecret = false;
        }
    }

    public function tearDown(): void
    {
        if ($this->_resetJWTSecret) {
            OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET} = '';
        }

        Tinebase_Core::getContainer()->set(RequestInterface::class, $this->_oldRequest);

        if ($this->_originalTestUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        }

        $this->_jsonTest->tearDown();

        parent::tearDown();
    }

    public function testGetDocumentForTempFile()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        $token = @end(explode('/', $editorCfg['document']['url']));

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        $response = $this->_uit->getDocument($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blub', (string)$response->getBody());
    }

    public function testUpdateStatus2Attachment()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('modlog not active');
        }

        $editorCfg = $this->_jsonTest->testGetEditorConfigForAttachment(false);

        $token = @end(explode('/', $editorCfg['document']['url']));
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($accessToken);

        file_put_contents('tine20:///Tinebase/folders/shared/testUpdated.txt', 'blubblub');
        file_put_contents('tine20:///Tinebase/folders/shared/changes', 'shalala');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/testUpdated.txt',
            'changesurl' => 'tine20:///Tinebase/folders/shared/changes',
            'history' => [
                'meAboutHistory'    => 'what a story! ten thousands of years of epic madness and no end to it!',
                'changes'           => 'changes, the only constant in time',
                'serverVersion'     => '42',
            ],
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));

        $updatedAccessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($updatedAccessToken);
        static::assertGreaterThan($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION},
            $updatedAccessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION},
            'revision did not increase');
        static::assertSame('blubblub', file_get_contents('tine20://' .
            Tinebase_FileSystem::getInstance()->getPathOfNode($accessToken
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, true)));

        $fe = new OnlyOfficeIntegrator_Frontend_Json();
        $history = $fe->getHistory($updatedAccessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY});
        static::assertArrayHasKey('currentVersion', $history);
        static::assertSame($updatedAccessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION},
            $history['currentVersion']);
        static::assertArrayHasKey('history', $history);
        static::assertCount(2, $history['history']);
    }

    public function testUpdateStatus2DocumentTempFileWithClosedToken()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        (new OnlyOfficeIntegrator_Frontend_Json())->tokenSignOut($editorCfg['document']['key']);
        $token = @end(explode('/', $editorCfg['document']['url']));
        file_put_contents('tine20:///Tinebase/folders/shared/testUpdated.txt', 'blubblub');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/testUpdated.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));

        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($accessToken);
        static::assertSame('blubblub', file_get_contents(
            Tinebase_TempFile::getInstance()->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID})
                ->path));
    }

    public function testUpdateStatusDocumentTempFileWithClosedToken()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        (new OnlyOfficeIntegrator_Frontend_Json())->tokenSignOut($editorCfg['document']['key']);
        $token = @end(explode('/', $editorCfg['document']['url']));
        file_put_contents('tine20:///Tinebase/folders/shared/testUpdated.txt', 'blubblub');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 6,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/testUpdated.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));

        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($accessToken);
        static::assertSame('blubblub', file_get_contents(
            Tinebase_TempFile::getInstance()->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID})
                ->path));
    }

    public function testUpdateStatusDocumentTempFile()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        $token = @end(explode('/', $editorCfg['document']['url']));
        file_put_contents('tine20:///Tinebase/folders/shared/testUpdated.txt', 'blubblub');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 6,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/testUpdated.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));

        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($accessToken);
        static::assertSame('blubblub', file_get_contents(
            Tinebase_TempFile::getInstance()->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID})
                ->path));
    }

    public function testUpdateStatusDocumentTempFileRename()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        $token = @end(explode('/', $editorCfg['document']['url']));
        file_put_contents('tine20:///Tinebase/folders/shared/testUpdated.docx', 'blubblub');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 6,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/testUpdated.docx',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));

        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        static::assertNotNull($accessToken);
        static::assertSame('blubblub', file_get_contents(($tempFile = Tinebase_TempFile::getInstance()
            ->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}))->path));
        static::assertSame('test.docx', $tempFile->name);
    }

    public function testGetDocumentParameterException1()
    {
        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('auth token missing');
        $this->_uit->getDocument('');
    }

    public function testGetDocumentParameterException2()
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/uoh']],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('jwt mismatch (maybe tine20Url confing is missing?): http://unittest/uoh !== http://unittest/shalala');
        $this->_uit->getDocument('');
    }


    public function testGetDocumentParameterException3()
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('parameter token needs to be a string');
        static::expectExceptionCode(400);
        $this->_uit->getDocument('');
    }

    public function testGetDocument()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);

        $parts = explode('/', $editorCfg['document']['url']);
        $token = $parts[count($parts) - 1];

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        $response = $this->_uit->getDocument($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blub', (string)$response->getBody());
    }

    public function testGetDocumentOldRevision()
    {
        if (!Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            static::markTestSkipped('fs modlog required');
        }
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);

        $parts = explode('/', $editorCfg['document']['url']);
        $token = $parts[count($parts) - 1];

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/test.txt', 'bla');

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        $response = $this->_uit->getDocument($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame('blub', (string)$response->getBody());
    }

    public function testGetDocumentOutdatedToken()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        $token = @end(explode('/', $editorCfg['document']['url']));

        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME,
            [
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN => (string)(Tinebase_DateTime::now()->subSecond(
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME} + 1))
            ],
            OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN . ' = "' . $token . '"');

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token not valid');
        static::expectExceptionCode(404);

        $this->_uit->getDocument($token);
    }

    public function testGetDocumentDeletedFile()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        $token = @end(explode('/', $editorCfg['document']['url']));
        Tinebase_FileSystem::getInstance()->unlink('/Tinebase/folders/shared/ootest/test.txt');

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => ['url' => 'http://unittest/shalala']],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256')));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('node not found');
        static::expectExceptionCode(404);

        $this->_uit->getDocument($token);
    }

    public function testUpdateStatusDocumentChangedImplicitRename()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/changes', 'changesContent');
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.docx', 'blubblub');
        $token = @end(explode('/', $editorCfg['document']['url']));

        $tokenRecord = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        $tokenRecord->setId(null);
        $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID} = Tinebase_Record_Abstract::generateUID();
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->create($tokenRecord);

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/testUpdated.docx',
            'history' => ['a' => 'b'],
            'changesurl' => 'tine20:///Tinebase/folders/shared/ootest/changes',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));
        static::assertFalse(Tinebase_FileSystem::getInstance()->fileExists('/Tinebase/folders/shared/ootest/test.txt'),
            'expect file to be renamed');
        static::assertSame('blubblub', file_get_contents('tine20:///Tinebase/folders/shared/ootest/test.docx'));
        static::assertSame(2, ($allTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' =>
                    Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ])))->count());

        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.docx');
        foreach ($allTokens as $token) {
            static::assertSame($node->getId(), $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            static::assertSame($node->revision, $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION});
            static::assertEquals(1, $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED});
            static::assertEquals(2, $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION});
        }

        $notes = Tinebase_Notes::getInstance()->searchNotes(new Tinebase_Model_NoteFilter([
            ['field' => 'record_id', 'operator' => 'equals', 'value' => $node->getId()],
            ['field' => 'record_model', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_Node::class],
        ]), new Tinebase_Model_Pagination([
            'sort' => 'seq',
            'dir' => 'DESC',
            'limit' => 2,
        ]));

        static::assertStringContainsString(' name (test.txt -> test.docx)', $notes->getFirstRecord()->note);
        static::assertStringNotContainsString(' name (test.txt -> test.docx)', $notes->getLastRecord()->note);
        $_ = Tinebase_Translation::getTranslation(Tinebase_Config::APP_NAME, Tinebase_Core::getLocale());
        static::assertStringContainsString(' ' . $_->_('size') . ' (', $notes->getFirstRecord()->note);
        
        static::assertSame('changesContent', file_get_contents('tine20://' .
            OnlyOfficeIntegrator_Controller::getRevisionsChangesPath() . '/' . $node->getId() . '/' . $node->revision));
        $history = OnlyOfficeIntegrator_Controller_History::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_History::class, [
                ['field' => OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID, 'operator' => 'equals', 'value' => $node->getId()],
                ['field' => OnlyOfficeIntegrator_Model_History::FLDS_NODE_REVISION, 'operator' => 'equals', 'value' => $node->revision],
            ]))->getFirstRecord();
        static::assertNotNull($history, 'history entry not found');
        static::assertSame([
            'history'   => $reqBody['history'],
            'created'   => $history->{OnlyOfficeIntegrator_Model_History::FLDS_JSON}['created'],
            'user'      => [
                'id'        => Tinebase_Core::getUser()->getId(),
                'name'      => Tinebase_Core::getUser()->getTitle(),
            ],
        ], $history->{OnlyOfficeIntegrator_Model_History::FLDS_JSON});

        static::assertSame($node->revision, $history->{OnlyOfficeIntegrator_Model_History::FLDS_VERSION});
    }

    public function testUpdateStatusDocumentChanged()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.txt', 'blubblub');
        $token = @end(explode('/', $editorCfg['document']['url']));

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/testUpdated.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));
        static::assertSame('blubblub', file_get_contents('tine20:///Tinebase/folders/shared/ootest/test.txt'));
        static::assertNull(OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord());
    }

    public function testUpdateStatusDocumentConflict()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);

        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.txt', 'blubblub');
        $token = @end(explode('/', $editorCfg['document']['url']));

        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME,
            [
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN => (string)(Tinebase_DateTime::now()->subHour(2)),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED => 1,
            ],
            OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN . ' = "' . $token . '"');

        $node = Tinebase_FileSystem::getInstance()->stat('/Tinebase/folders/shared/ootest/test.txt');
        (new OnlyOfficeIntegrator_Frontend_Json())->getEditorConfigForNodeId($node->getId(), $node->revision);

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/testUpdated.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->updateStatus($token);

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['error' => 0], json_decode((string)$response->getBody(), true));
        static::assertFalse(Tinebase_FileSystem::getInstance()->fileExists('/Tinebase/folders/shared/ootest/test.txt'));
        static::assertSame('blubblub', file_get_contents('tine20:///Tinebase/folders/shared/ootest/test.conflict.txt'));
        static::assertNull(OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord());
    }

    public function testUpdateStatusOutdatedToken()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        $token = @end(explode('/', $editorCfg['document']['url']));

        Tinebase_Core::getDb()->update(SQL_TABLE_PREFIX . OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME,
            [
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN => (string)(Tinebase_DateTime::now()->subDay(2))
            ],
            OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN . ' = "' . $token . '"');

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/testUpdated.txt',
        ]));
        rewind($fh);
        file_put_contents('tine20:///Tinebase/folders/shared/ootest/testUpdated.txt', 'test');
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        try {
            $this->_uit->updateStatus($token);
        } catch (Tinebase_Exception_Expressive_HttpStatus $e) {
            $this->assertStringContainsString('token not valid', $e->getMessage());
            $this->assertTrue(Tinebase_FileSystem::getInstance()->fileExists('/Filemanager/folders/shared/OOIQuarantine'));
            $node = Tinebase_FileSystem::getInstance()->stat('/Filemanager/folders/shared/OOIQuarantine');
            $files = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($node->getId());
            $this->assertSame(1, $files->count());
            $this->assertSame('test', file_get_contents('tine20:///Filemanager/folders/shared/OOIQuarantine/' .
                $files->getFirstRecord()->name));

            return;
        }
        $this->fail('expected ' . Tinebase_Exception_Expressive_HttpStatus::class . ' to be thrown');
    }

    public function testUpdateStatusParameterException()
    {
        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('parameter token needs to be a string');
        static::expectExceptionCode(400);

        $this->_uit->updateStatus('');
    }

    public function testUpdateStatusAuthTokenMissing()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asdf',
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_AccessDenied:: class);
        static::expectExceptionMessage('auth token missing');
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusAuthTokenMissing2()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asdf',
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'shalalalala')
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_AccessDenied:: class);
        static::expectExceptionMessage('auth token missing');
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusWrongAuthToken()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asdf',
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer dfgsfdsfasfd')
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('auth token not valid');
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusWrongAuthToken2()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asdf',
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);
        $reqBody['a'] = 'b';

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_AccessDenied::class);
        static::expectExceptionMessage('auth token doesn\'t match body');
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusBadBody()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'key' => 'asdf',
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('bad message format, status or key not set');
        static::expectExceptionCode(400);
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusBadBody2()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'url' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('bad message format, status or key not set');
        static::expectExceptionCode(400);
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusBadBody3()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asfdffasdf',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('bad message format, url not set');
        static::expectExceptionCode(400);
        $this->_uit->updateStatus('asdf');
    }

    public function testUpdateStatusWithStatusNot2()
    {
        $expected = '{
    "error": 0
}';
        for ($i = 0; $i < 10; ++$i) {

            if (2 === $i || 6 === $i) continue;

            $fh = fopen('php://memory', 'rw');
            fwrite($fh, json_encode($reqBody = [
                'status' => $i,
                'key' => 'asfdffasdf',
            ]));
            rewind($fh);

            Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
                ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                        OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
                ->withBody(new \Zend\Diactoros\Stream($fh)));

            static::assertSame($expected, (string)$this->_uit->updateStatus('asdf')->getBody());
        }
    }

    public function testUpdateStatusReadOnlyToken()
    {
        $url = $this->_jsonTest->testGetEmbedUrlForNodeId(false);
        $token = @end(explode('/', $url['url']));

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'adsfffasdf',
            'url' => 'tine20:///Tinebase/folders/shared/ootest/test.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token not valid');
        static::expectExceptionCode(404);
        $this->_uit->updateStatus($token);
    }

    public function testUpdateStatusBadKey()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        $token = @end(explode('/', $editorCfg['document']['url']));

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => 'asdfas',
            'url' => 'tine20:///Tinebase/folders/shared/ootest/test.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('token doesnt match key');
        static::expectExceptionCode(400);
        $this->_uit->updateStatus($token);
    }

    public function testUpdateStatusDeletedFile()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        Tinebase_FileSystem::getInstance()->unlink('/Tinebase/folders/shared/ootest/test.txt');
        $token = @end(explode('/', $editorCfg['document']['url']));

        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode($reqBody = [
            'status' => 2,
            'key' => $editorCfg['document']['key'],
            'url' => 'tine20:///Tinebase/folders/shared/ootest/test.txt',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class, (new \Zend\Diactoros\ServerRequest())
            ->withHeader('Authorization', 'Bearer ' . JWT::encode(['payload' => $reqBody],
                    OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionMessage('node not found');
        static::expectExceptionCode(404);
        $this->_uit->updateStatus($token);
    }

    public function testCallCmdServiceDropFailure1()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $config = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        $key = $config['document']['key'];
        $userIds = Tinebase_User::getInstance()->getAllUserIdsFromSqlBackend();

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(500, [], '{"error":0}'));
        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        static::expectException(Tinebase_Exception_Backend::class);
        static::expectExceptionMessage('onlyoffice command service did not responde with status code 200');

        $this->_uit->callCmdServiceDrop($key, $userIds);
    }

    public function testCallCmdServiceDropFailure2()
    {
        Tinebase_FileSystem::getInstance()->createAclNode('/Filemanager/folders/shared/ootest');
        $config = $this->_jsonTest->testGetEditorConfigForTempFile(false);
        $key = $config['document']['key'];
        $userIds = Tinebase_User::getInstance()->getAllUserIdsFromSqlBackend();

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":2}'));
        OnlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        //we only accept error code 0,1
        static::assertFalse($this->_uit->callCmdServiceDrop($key, $userIds),
            'onlyoffice cmd service returns the unacceptable error code');
    }

    public function testCallCmdServiceInfoFailure1()
    {
        $editorCfg = $this->_jsonTest->testGetEditorConfigForNodeId(false);
        $token = @end(explode('/', $editorCfg['document']['url']));
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        static::assertNotNull($accessToken);

        $httpTestClient = new Zend_Http_Client_Adapter_Test();
        $httpTestClient->setResponse(new Zend_Http_Response(200, [], '{"error":3}'));
        onlyOfficeIntegrator_Controller::getInstance()->setCmdServiceClientAdapter($httpTestClient);

        static::expectException(Tinebase_Exception_Backend::class);
        static::expectExceptionMessage('onlyoffice cmd service returns the unacceptable error code');

        //we only accept error code 0,1,4
        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $this->_uit->callCmdServiceInfo($accessToken);
    }

    public function testIsInMaintenanceMode()
    {
        OnlyOfficeIntegrator_Controller::getInstance()->goIntoMaintenanceMode();
        static::assertTrue($this->_uit->isInMaintenanceMode(),'still have alive tokens after set to MaintenanceMode');
    }
}
