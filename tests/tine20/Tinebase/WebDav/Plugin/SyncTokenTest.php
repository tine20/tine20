<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_WebDav_Plugin_SyncTokenTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    /**
     * @var Tinebase_WebDav_Plugin_SyncToken
     */
    protected $plugin;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase WebDav Plugin SyncToken Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        parent::setupCalendarContainer();

        $this->setupCalendarContent();

        $this->plugin = new Tinebase_WebDav_Plugin_SyncToken();
        $this->server->addPlugin($this->plugin);
    }

    /**
     * tear down tests
     */
    public function tearDown()
    {
        parent::tearDown();

        Tinebase_Container::getInstance()->getContentBackend()->delete($this->objects['containerContent']);
    }

    protected function setupCalendarContent()
    {
        /**
         * @var Tinebase_Backend_Sql
         */
        $contentBackend = Tinebase_Container::getInstance()->getContentBackend();
        $this->objects['containerContent'] = new Tinebase_Model_ContainerContent(array(
            'action'          => Tinebase_Model_ContainerContent::ACTION_CREATE,
            'record_id'       => 'testRecordId',
            'time'            => Tinebase_DateTime::now(),
            'container_id'    => $this->objects['initialContainer']->id,
            'content_seq'     => 1,
        ));
        $contentBackend->create($this->objects['containerContent']);
    }

    /**
     * test getSupportedReportSet method
     */
    public function testGetSupportedReportSetForCalendarNode()
    {
        $set = $this->plugin->getSupportedReportSet('/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id);

        $this->assertContains('{DAV:}sync-collection', $set);
    }

    /**
     * test propfind sync-token request
     */
    public function testPropfindSyncToken()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <A:propfind xmlns:A="DAV:">
                     <A:prop>
                        <A:sync-token/>
                     </A:prop>
                 </A:propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:sync-token>http://tine20.net/ns/sync/1</d:sync-token></d:prop><d:status>HTTP/1.1 200 OK</d:status></d:propstat></d:response></d:multistatus>', $this->response->body);
    }

    /**
     * test sync-collection request
     */
    public function testSyncCollection()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
                <A:sync-collection xmlns:A="DAV:">
                    <A:sync-token>http://tine20.net/ns/sync/0</A:sync-token>
                    <A:sync-level>1</A:sync-level>
                    <A:prop>
                        <A:getetag/>
                        <A:getcontenttype/>
                    </A:prop>
                </A:sync-collection>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->objects['initialContainer']->id,
            'HTTP_DEPTH'     => '1',
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        $this->assertContains('<d:sync-token>http://tine20.net/ns/sync/1</d:sync-token></d:multistatus>', $this->response->body);
    }
}