<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Frontend_WebDAV_Record
 */
class Tinebase_Frontend_WebDAV_RecordTest extends TestCase
{
    /**
     * testDownloadAttachment
     */
    public function testDownloadAttachment()
    {
        if (! Setup_Controller::getInstance()->isInstalled('Calendar')) {
            $this->markTestSkipped('Calendar not installed');
        }
        
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Wakeup',
            'dtstart'     => '2009-03-25 06:00:00',
            'dtend'       => '2009-03-25 06:15:00',
        ));
        $tempFile = $this->_getTempFile();
        $event->attachments = array(array('tempFile' => array('id' => $tempFile->getId())));
        $savedEvent = Calendar_Controller_Event::getInstance()->create($event);
        
        $this->assertTrue(isset($savedEvent->attachments) && count($savedEvent->attachments) === 1);
        
        // try to fetch attachment of event
        $recordCollection = new Tinebase_Frontend_WebDAV_Record('Calendar/records/Calendar_Model_Event/' . $savedEvent->getId());
        $file = $recordCollection->getChild($tempFile->name);
        $this->assertEquals('text/plain', $file->getContentType());
        $this->assertEquals(17, $file->getSize());
    }
}
