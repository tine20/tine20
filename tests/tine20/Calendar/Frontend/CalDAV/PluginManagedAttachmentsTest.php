<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @subpackage  CalDAV
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once __DIR__ . '/../../../../../tine20/vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Calendar_Frontend_CalDAV_PluginManagedAttachments
 */
class Calendar_Frontend_CalDAV_PluginManagedAttachmentsTest extends TestCase
{
    /**
     * 
     * @var Sabre\DAV\Server
     */
    protected $server;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp()
    {
        $this->calDAVTests = new Calendar_Frontend_WebDAV_EventTest();
        $this->calDAVTests->setup();
        
        parent::setUp();
        
        $this->hostname = 'tine.example.com';
        $this->originalHostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        $_SERVER['HTTP_HOST'] = $this->hostname;
        
        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        
        $this->plugin = new Calendar_Frontend_CalDAV_PluginManagedAttachments();
        
        $this->server->addPlugin($this->plugin);
        
        
        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    public function tearDown()
    {
        if ($this->originalHostname) {
            $_SERVER['HTTP_HOST'] = $this->originalHostname;
        }
        $this->calDAVTests->tearDown();
    }
    
    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();
        
        $this->assertEquals('calendarManagedAttachments', $pluginName);
    }
    
    /**
     * test testAddAttachment 
     */
    public function testAddAttachment()
    {
        $event = $this->calDAVTests->testCreateEventWithInternalOrganizer();
        
        $request = new Sabre\HTTP\Request(array(
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'HTTP_CONTENT_DISPOSITION' => 'attachment;filename="agenda.txt"',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/' . 
                Tinebase_Core::getUser()->contact_id . '/'. 
                $event->getRecord()->container_id . '/' .
                $event->getRecord()->uid . '.ics?action=attachment-add',
            'QUERY_STRING'   => 'action=attachment-add',
            'HTTP_DEPTH'     => '0',
        ));
        
        $agenda = 'HELLO WORLD';
        $request->setBody($agenda);

        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $vcalendar = stream_get_contents($this->response->body);
//         echo $vcalendar;
        
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()
        ->getRecordAttachments($event->getRecord());
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
        $this->assertEquals(1, $attachments->count());
        $this->assertEquals('agenda.txt', $attachments[0]->name);
    }
    
    /**
     * test testOverwriteAttachment
     * 
     * NOTE: current iCal can not update, but re-adds/overwrides if
     *       you drag and drop an attachment twice
     */
    public function testOverwriteAttachment()
    {
        $event = $this->calDAVTests->createEventWithAttachment();
        
        $request = new Sabre\HTTP\Request(array(
                'HTTP_CONTENT_TYPE' => 'text/plain',
                'HTTP_CONTENT_DISPOSITION' => 'attachment;filename="agenda.html"',
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI'    => '/calendars/' .
                Tinebase_Core::getUser()->contact_id . '/'.
                $event->getRecord()->container_id . '/' .
                $event->getRecord()->uid . '.ics?action=attachment-add',
                'QUERY_STRING'   => 'action=attachment-add',
                'HTTP_DEPTH'     => '0',
        ));
        
        $agenda = 'GODDBYE WORLD';
        $request->setBody($agenda);
        
        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $vcalendar = stream_get_contents($this->response->body);
        //         echo $vcalendar;
        
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()
        ->getRecordAttachments($event->getRecord());
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
        $this->assertEquals(1, $attachments->count());
        $this->assertEquals('agenda.html', $attachments[0]->name);
    }
    
    public function testUpdateAttachment()
    {
        $event = $this->calDAVTests->createEventWithAttachment();
        $attachmentNode = $event->getRecord()->attachments->getFirstRecord();
        
        $request = new Sabre\HTTP\Request(array(
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'HTTP_CONTENT_DISPOSITION' => 'attachment;filename=agenda.txt',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/calendars/' .
            Tinebase_Core::getUser()->contact_id . '/'.
            $event->getRecord()->container_id . '/' .
            $event->getRecord()->uid . '.ics?action=attachment-update&managed-id='.$attachmentNode->hash,
            'QUERY_STRING'   => 'action=attachment-update&managed-id='.$attachmentNode->hash,
            'HTTP_DEPTH'     => '0',
        ));
        
        $agenda = 'GODDBYE WORLD';
        $request->setBody($agenda);
        
        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $vcalendar = stream_get_contents($this->response->body);
//         echo $vcalendar;
        
//         $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
         $this->assertNotContains($attachmentNode->hash, $vcalendar, 'old managed-id');
        //@TODO assert URI
        //@TODO /fetch attachement & assert contents
    }
    
    public function testRemoveAttachment()
    {
        $event = $this->calDAVTests->createEventWithAttachment();
        $attachmentNode = $event->getRecord()->attachments->getFirstRecord();
        
        $request = new Sabre\HTTP\Request(array(
                'HTTP_CONTENT_TYPE' => 'text/plain',
                'HTTP_CONTENT_DISPOSITION' => 'attachment;filename=agenda.txt',
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI'    => '/calendars/' .
                Tinebase_Core::getUser()->contact_id . '/'.
                $event->getRecord()->container_id . '/' .
                $event->getRecord()->uid . '.ics?action=attachment-remove&managed-id='.$attachmentNode->hash,
                'QUERY_STRING'   => 'action=attachment-remove&managed-id='.$attachmentNode->hash,
                'HTTP_DEPTH'     => '0',
        ));
        
        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $vcalendar = stream_get_contents($this->response->body);
//         echo $vcalendar;
        
//         $this->assertEquals('HTTP/1.1 204 No Content', $this->response->status);
        $this->assertNotContains('ATTACH;MANAGED-ID=', $vcalendar, $vcalendar);
        
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($event->getRecord());
        $this->assertEquals(0, $attachments->count());
    }
}
