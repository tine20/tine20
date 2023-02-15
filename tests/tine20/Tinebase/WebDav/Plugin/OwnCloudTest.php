<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */


/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Tinebase_WebDav_Plugin_OwnCloudTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    const REQUEST_BODY = '<?xml version="1.0" encoding="utf-8"?>
        <propfind xmlns="DAV:">
            <prop>
                <getlastmodified xmlns="DAV:"/>
                <getcontentlength xmlns="DAV:"/>
                <resourcetype xmlns="DAV:"/>
                <getetag xmlns="DAV:"/>
                <id xmlns="http://owncloud.org/ns"/>
            </prop>
        </propfind>';
    
    /**
     * base uri sent from owncloud client with different version
     *
     * @access public
     * @static
     */
    const BASE_URIV2_WEBDAV = '/remote.php/webdav';
    const BASE_URIV3_DAV_FILES_USERNAME = '/remote.php/dav/files/tine20admin';

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Plugin OwnCloud Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->plugin = new Tinebase_WebDav_Plugin_OwnCloud();

        // Create request, there is no in tinebase while running unittests, but the owncloud plugin needs an user agent
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n" .
            "Host: localhost\r\n" .
            "Content-Type: application/json\r\n" .
            "User-Agent: Mozilla/5.0 (Macintosh) mirall/2.2.4 (build 3709)\r\n"
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        $this->server->addPlugin($this->plugin);
    }

    /**
     * tear down tests
     */
    protected function tearDown(): void
{
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();

        $this->assertEquals('Tinebase_WebDav_Plugin_OwnCloud', $pluginName);
    }

    /**
     * test testGetProperties method
     */
    public function testGetRootsV2()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?><d:propfind xmlns:d="DAV:"><d:prop><d:resourcetype/></d:prop>M</d:propfind>';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV2_WEBDAV,
            'HTTP_DEPTH' => '1',
        ));
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->assertStringContainsString(self::BASE_URIV2_WEBDAV . '/shared', $responseDoc->textContent);
        $this->assertStringContainsString(self::BASE_URIV2_WEBDAV . '/Admin', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method
     */
    public function testGetRootsV3()
    {
        // fixme: owncloud client expect response path start with /dav/files/userLoginName too , /webdav/folder does not work anymore
        $body = '<?xml version="1.0" encoding="utf-8"?><d:propfind xmlns:d="DAV:"><d:prop><d:resourcetype/></d:prop></d:propfind>';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV3_DAV_FILES_USERNAME,
            'HTTP_DEPTH' => '1',
        ));

        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared', $responseDoc->textContent);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/Admin', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesV2()
    {
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName,
            'HTTP_DEPTH' => '0',
        ));
        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesV3()
    {
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV3_DAV_FILES_USERNAME,
            'HTTP_DEPTH' => '0',
        ));
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    protected function _assertQueryResponse($responseDoc, $query, $nodeLength = 1)
    {
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('owncloud', 'http://owncloud.org/ns');

        $nodes = $xpath->query($query);
        $this->assertEquals($nodeLength, $nodes->length, $responseDoc->saveXML());
        
        for($i = 0 ; $i < $nodeLength; $i++) {
            $nodeValue = $nodes->item($i)->nodeValue;
            $this->assertNotNull($nodeValue, $responseDoc->saveXML());
        }
    }

    /**
     * @param string|null $body
     * @return DOMDocument
     */
    protected function _execPropfindRequest($body = null, $request = null)
    {
        if (!$request) {
            $request = new Sabre\HTTP\Request(array(
                'REQUEST_METHOD' => 'PROPFIND',
                'REQUEST_URI' => self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName,
                'HTTP_DEPTH' => '0',
            ));
        }
        
        $request->setBody($body ?: static::REQUEST_BODY);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        return $responseDoc;
    }

    /**
     * test testGetSizeProperty
     */
    public function testGetSizePropertyV2()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
    <prop>
        <resourcetype xmlns="DAV:"/>
        <size xmlns="http://owncloud.org/ns"/>
    </prop>
</propfind>';
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:size';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName,
            'HTTP_DEPTH' => '0',
        ));
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetSizeProperty
     */
    public function testGetSizePropertyV3()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
    <prop>
        <resourcetype xmlns="DAV:"/>
        <size xmlns="http://owncloud.org/ns"/>
    </prop>
</propfind>';
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:size';
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV3_DAV_FILES_USERNAME . '/' . Tinebase_Core::getUser()->accountDisplayName,
            'HTTP_DEPTH' => '0',
        ));
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesForSharedDirectoryV2()
    {
        $webdavTree = new \Sabre\DAV\ObjectTree(new Tinebase_WebDav_Root());
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared');
        $node->createDirectory('unittestdirectory');
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        $node->createDirectory('subdir');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV2_WEBDAV . '/shared/unittestdirectory',
            'HTTP_DEPTH' => '1',
        ));
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query, 2);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/d:getetag';
        $this->_assertQueryResponse($responseDoc, $query, 2);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesForSharedDirectoryV3()
    {
        $webdavTree = new \Sabre\DAV\ObjectTree(new Tinebase_WebDav_Root());
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared');
        $node->createDirectory('unittestdirectory');
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        $node->createDirectory('subdir');

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV3_DAV_FILES_USERNAME . '/shared',
            'HTTP_DEPTH' => '1',
        ));

        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory', $responseDoc->textContent);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query, 2);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/d:getetag';
        $this->_assertQueryResponse($responseDoc, $query, 2);

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory',
            'HTTP_DEPTH' => '1',
        ));

        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory/subdir', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method with an invalid client
     */
    public function testInvalidOwnCloudVersion()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'OwnCloud client min version is "%s"!',
            Tinebase_WebDav_Plugin_OwnCloud::OWNCLOUD_MIN_VERSION
        ));

        // use old owncloud user agent!
        $request = Tinebase_Http_Request::fromString(
            "POST /index.php HTTP/1.1\r\n" .
            "Host: localhost\r\n" .
            "Content-Type: application/json\r\n" .
            "User-Agent: Mozilla/5.0 (Macintosh) mirall/1.5.0 (build 3709)\r\n"
        );
        Tinebase_Core::set('request', $request);
        $this->_execPropfindRequest();
    }

    /**
     * test testGetProperties method with alternate loginname config
     */
    public function testGetPropertiesWithAccountLoginName()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI' => self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountLoginName,
            'HTTP_DEPTH' => '0',
        ));
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query);
    }
}
