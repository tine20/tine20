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
    
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
             <A:propfind xmlns:A="DAV:">
               <A:prop>
                 <B:dropbox-home-URL xmlns:B="http://calendarserver.org/ns/"/>
               </A:prop>
             </A:propfind>';
    
        $request = new Sabre\HTTP\Request(array(
                'REQUEST_METHOD' => 'PROPFIND',
                'REQUEST_URI'    => '/' . Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id
        ));
        $request->setBody($body);
    
        $this->server->httpRequest = $request;
        $this->server->exec();
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cs:dropbox-home-URL/d:href');
        $dropboxUrl = $nodes->item(0)->nodeValue;
        $this->assertTrue(!! strstr($dropboxUrl, 'dropbox'), $dropboxUrl);
    }
    
    /**
     * test testAddAttachment 
     */
    public function testAddAttachment($disposition = 'attachment;filename="agenda.txt"', $filename = 'agenda.txt')
    {
        $event = $this->calDAVTests->testCreateRepeatingEventAndPutExdate();
        
        $request = new Sabre\HTTP\Request(array(
            'HTTP_CONTENT_TYPE' => 'text/plain',
            'HTTP_CONTENT_DISPOSITION' => $disposition,
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

        $vcalendar = $this->_execAndGetVCalendarFromRequest($request);
        
        $baseAttachments = Tinebase_FileSystem_RecordAttachments::getInstance()
            ->getRecordAttachments($event->getRecord());
        $exdateAttachments = Tinebase_FileSystem_RecordAttachments::getInstance()
            ->getRecordAttachments($event->getRecord()->exdate[0]);
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
        $this->assertEquals(1, $baseAttachments->count());
        $this->assertEquals($filename, $baseAttachments[0]->name);
        $this->assertEquals(1, $exdateAttachments->count());
        $this->assertEquals($filename, $exdateAttachments[0]->name);
    }

    /**
     * @group nogitlabci
     * gitlabci: iconv(): Detected an illegal character in input string
     */
    public function testAddAttachmentWithUmlauts()
    {
        //$filename = 'Reservierungsbestätigung : OTTER.txt';
        $disposition = "filename=\"Reservierungsbesta?tigung _ OTTER.txt\";filename*=utf-8''Reservierungsbesta%CC%88tigung%20_%20OTTER.txt";
        $this->testAddAttachment($disposition, 'Reservierungsbestätigung _ OTTER.txt');
    }
    
    /**
     * test testOverwriteAttachment
     * 
     * NOTE: current iCal can not update, but re-adds/overwrides if
     *       you drag and drop an attachment twice
     */
    public function testOverwriteAttachment()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

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

        $vcalendar = $this->_execAndGetVCalendarFromRequest($request);
        
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()
        ->getRecordAttachments($event->getRecord());
        
        $this->assertEquals('HTTP/1.1 201 Created', $this->response->status);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
        $this->assertEquals(1, $attachments->count());
        $this->assertEquals('agenda.html', $attachments[0]->name);
    }
    
    public function testUpdateAttachment()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

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
        
        $vcalendar = $this->_execAndGetVCalendarFromRequest($request);
        $this->assertContains('ATTACH;MANAGED-ID='. sha1($agenda), $vcalendar, $vcalendar);
         $this->assertNotContains($attachmentNode->hash, $vcalendar, 'old managed-id');
        //@TODO assert URI
        //@TODO /fetch attachment & assert contents
    }
    
    public function testRemoveAttachment()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';

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
        
        $vcalendar = $this->_execAndGetVCalendarFromRequest($request);
        $this->assertNotContains('ATTACH;MANAGED-ID=', $vcalendar, $vcalendar);
        
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($event->getRecord());
        $this->assertEquals(0, $attachments->count());
    }

    /**
     * exec request and get vcalendar
     *
     * @param Sabre\HTTP\Request $request
     * @return string
     */
    protected function _execAndGetVCalendarFromRequest($request)
    {
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertTrue(is_resource($this->response->body), 'expecting a resource, got: ' . $this->response->body);
        $vcalendar = stream_get_contents($this->response->body);

//         echo $vcalendar;

        return $vcalendar;
    }
    
    public function testCreateRecurringExceptionWithManagedBaseAttachment()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
        
        $event = $this->calDAVTests->createEventWithAttachment();
        
        // assemble vcalendar with exception
        $eventWithExdate = clone $event;
        $exception = clone $eventWithExdate->getRecord();
        $eventWithExdate->getRecord()->exdate->addRecord($exception);
        $exception->exdate = NULL;
        $exception->rrule = NULL;
        $exception->dtstart->addDay(5)->addHour(1);
        $exception->dtend->addDay(5)->addHour(1);
        $exception->setRecurId($event->getRecord()->getId());

        $event->put($eventWithExdate->get());
        
        $createdException = $event->getRecord()->exdate
            ->filter('recurid', '9d28b78f-aa6d-44fc-92f6-5ab98d35d692-2011-10-09 09:00:00')
            ->getFirstRecord();
        
        $this->assertEquals(1, $createdException->attachments->count());
        
        $attachment = $createdException->attachments->getFirstRecord();
        $this->assertEquals('agenda.html', $attachment->name);
    }

    public function testDropBoxMKCol()
    {
        $event = $this->calDAVTests->testCreateEventWithInternalOrganizer();
        
        $body = '';
        $request = new Sabre\HTTP\Request(array(
                'REQUEST_METHOD' => 'MKCOL',
                'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/dropbox/' . 
                    $event->getRecord()->getId() . '.dropbox'
        ));
        $request->setBody($body);
        
        $this->server->httpRequest = $request;
        $this->server->exec();
        
        // attachement folder is managed by tine20 itself
        $this->assertEquals('HTTP/1.1 405 Method Not Allowed', $this->response->status);
    }
    
    public function testDropBoxPut()
    {
        $event = $this->calDAVTests->testCreateEventWithInternalOrganizer();
    
        $request = new Sabre\HTTP\Request(array(
                'REQUEST_METHOD' => 'PUT',
                'REQUEST_URI'    => '/calendars/' . Tinebase_Core::getUser()->contact_id . '/dropbox/' .
                $event->getRecord()->getId() . '.dropbox/agenda.txt'
        ));
        
        $agenda = 'HELLO WORLD';
        $request->setBody($agenda);
    
        $this->server->httpRequest = $request;
        $this->server->exec();
        
//         echo $this->response->body;
        $attachments = Tinebase_FileSystem_RecordAttachments::getInstance()
        ->getRecordAttachments($event->getRecord());
        
        $this->assertEquals(1, $attachments->count());
        $this->assertEquals('agenda.txt', $attachments[0]->name);
    }
}
