<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Message\RequestInterface;


class MatrixSynapseIntegrator_ControllerTests extends TestCase
{
    /**
     * @var MatrixSynapseIntegrator_Controller
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


    public function setUp(): void
{
        parent::setUp();

        $this->_oldRequest = Tinebase_Core::getContainer()->get(RequestInterface::class);

        $this->_uit = MatrixSynapseIntegrator_Controller::getInstance();

    }

    public function tearDown(): void
{
        Tinebase_Core::getContainer()->set(RequestInterface::class, $this->_oldRequest);

        if ($this->_originalTestUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        }

        parent::tearDown();
    }

    public function testCheckCredentialsBadBody1()
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class,
            new \Zend\Diactoros\ServerRequest([], [], null, null, 'php://memory'));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionCode(400);
        static::expectExceptionMessage('illegal or missing json body');
        $this->_uit->checkCredentials();
    }

    public function testCheckCredentialsBadBody2()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'user' => [
                'id' => ''
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionCode(400);
        static::expectExceptionMessage('illegal or missing json body');
        $this->_uit->checkCredentials();
    }

    public function testCheckCredentialsBadBody3()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'user' => [
                'password' => ''
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        static::expectException(Tinebase_Exception_Expressive_HttpStatus::class);
        static::expectExceptionCode(400);
        static::expectExceptionMessage('illegal or missing json body');
        $this->_uit->checkCredentials();
    }

    public function testCheckCredentialsAuth()
    {
        $this->_skipIfLDAPBackend();

        $mxid = '@' . Tinebase_Core::getUser()->accountLoginName . ':' .
            MatrixSynapseIntegrator_Controller::getInstance()->getMatrixDomain();
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'user' => [
                'id'       => $mxid,
                'password' => Tinebase_Helper::array_value('password', TestServer::getInstance()->getTestCredentials())
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->checkCredentials();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['auth' => [
            'success'   => true,
            'mxid'      => $mxid,
            'profile'   => [
                'display_name'  => Tinebase_Core::getUser()->accountDisplayName,
                'three_pids'    => [
                    [
                        'medium'    => 'email',
                        'address'   => Tinebase_Core::getUser()->accountEmailAddress,
                    ]
                ]
            ]
        ]], json_decode((string)$response->getBody(), true));
    }

    public function testCheckCredentialsAuthFail1()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'user' => [
                'id'       => '',
                'password' => ''
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->checkCredentials();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame(['auth' => ['success' => false]], json_decode((string)$response->getBody(), true));
    }

    public function testDirectoryByName()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'by' => 'name',
            'search_term' => Tinebase_Core::getUser()->accountDisplayName
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->directory();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame([
            'limited' => false,
            'results' => [
                [
                    'display_name'  => Tinebase_Core::getUser()->accountDisplayName,
                    'user_id'       => Tinebase_Core::getUser()->accountLoginName,
                ]
            ]
        ], json_decode((string)$response->getBody(), true));
    }

    public function testDirectoryByThreePid()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'by' => 'threepid',
            'search_term' => Tinebase_Core::getUser()->accountEmailAddress
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->directory();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame([
            'limited' => false,
            'results' => [
                [
                    'display_name'  => Tinebase_Core::getUser()->accountDisplayName,
                    'user_id'       => Tinebase_Core::getUser()->accountLoginName,
                ]
            ]
        ], json_decode((string)$response->getBody(), true));
    }

    public function testIdentity()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'lookup' => [
                'medium'  => 'email',
                'address' => Tinebase_Core::getUser()->accountEmailAddress,
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->identity();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame([
                'medium' => 'email',
                'address' => Tinebase_Core::getUser()->accountEmailAddress,
                'id' => [
                    'type' => 'localpart',
                    'value' => Tinebase_Core::getUser()->accountLoginName,
                ]
        ], json_decode((string)$response->getBody(), true));
    }

    public function testIdentityBatch()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'lookup' => [
                [
                    'medium'  => 'email',
                    'address' => Tinebase_Core::getUser()->accountEmailAddress,
                ], [
                    'medium'  => 'email',
                    'address' => 'no hit data',
                ]
            ]
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->identity();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame([
            'lookup' => [[
                'medium' => 'email',
                'address' => Tinebase_Core::getUser()->accountEmailAddress,
                'id' => [
                    'type' => 'localpart',
                    'value' => Tinebase_Core::getUser()->accountLoginName,
                ]
            ]]
        ], json_decode((string)$response->getBody(), true));
    }

    public function testProfile()
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, json_encode([
            'mxid' => '',
            'localpart' => Tinebase_Core::getUser()->accountLoginName,
            'domain' => '',
        ]));
        rewind($fh);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Zend\Diactoros\ServerRequest())->withBody(new \Zend\Diactoros\Stream($fh)));

        $response = $this->_uit->profile();

        static::assertInstanceOf(\Zend\Diactoros\Response::class, $response);
        static::assertSame([
            'profile'   => [
                'display_name'  => Tinebase_Core::getUser()->accountDisplayName,
                'three_pids'    => [
                    [
                        'medium'    => 'email',
                        'address'   => Tinebase_Core::getUser()->accountEmailAddress,
                    ]
                ]
            ]
        ], json_decode((string)$response->getBody(), true));
    }
}
