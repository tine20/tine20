<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Command_SendMailTests extends Syncroton_Command_ATestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync SendMail command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test sending emails
     */
    public function testSendMail()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <SendMail xmlns="uri:ComposeMail">
            <ClientId>SendMail-1044646665832</ClientId>
            <SaveInSentItems/>
            <Mime>Date: Wed, 08 Aug 2012 06:14:19 +0200&#13;
Subject: Fwd: Test&#13;
Message-ID: &lt;99yw0o3w2t3mjrwd7fxufhwh.1344399259064@email.android.com&gt;&#13;
From: tine20admin@caldav.net&#13;
To: "Tine 2.0 Admin Account" &lt;tine20admin@caldav.net&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
VGVzdAoKLS0tLS0tLS0gT3JpZ2luYWwgTWVzc2FnZSAtLS0tLS0tLQpTdWJqZWN0OiBUZXN0CkZy&#13;
b206ICJUaW5lIDIuMCBBZG1pbiBBY2NvdW50IiA8dGluZTIwYWRtaW5AY2FsZGF2Lm5ldD4KVG86&#13;
IHRpbmUyMGFkbWluQGNhbGRhdi5uZXQKQ0M6IAoKCg==&#13;
</Mime></SendMail>'
        );
        
        $sendMail = new Syncroton_Command_SendMail($doc, $this->_device, null);
        $sendMail->handle();
        $responseDoc = $sendMail->getResponse();
        
        $this->assertEquals(null, $responseDoc);
    }
    
    /**
     * test sending emails
     */
    public function testSendMailAS12()
    {
        // delete folder created above
        $doc = 'Date: Wed, 08 Aug 2012 06:14:19 +0200&#13;
Subject: Fwd: Test&#13;
Message-ID: &lt;99yw0o3w2t3mjrwd7fxufhwh.1344399259064@email.android.com&gt;&#13;
From: tine20admin@caldav.net&#13;
To: "Tine 2.0 Admin Account" &lt;tine20admin@caldav.net&gt;&#13;
MIME-Version: 1.0&#13;
Content-Type: text/plain; charset=utf-8&#13;
Content-Transfer-Encoding: base64&#13;
&#13;
VGVzdAoKLS0tLS0tLS0gT3JpZ2luYWwgTWVzc2FnZSAtLS0tLS0tLQpTdWJqZWN0OiBUZXN0CkZy&#13;
b206ICJUaW5lIDIuMCBBZG1pbiBBY2NvdW50IiA8dGluZTIwYWRtaW5AY2FsZGF2Lm5ldD4KVG86&#13;
IHRpbmUyMGFkbWluQGNhbGRhdi5uZXQKQ0M6IAoKCg==&#13;';
        
        $sendMail = new Syncroton_Command_SendMail($doc, $this->_device, array(
            'contentType' => 'message/rfc822', 
            'policyKey' => 0, 
            'saveInSent' => 'T',
            'collectionId' => null,
            'itemId' => null
        ));
        $sendMail->handle();
        $responseDoc = $sendMail->getResponse();
        
        $this->assertEquals(null, $responseDoc);
    }
    
    /**
     * test sending emails failure
     */
    public function testSendMailException()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <SendMail xmlns="uri:ComposeMail">
            <ClientId>SendMail-1044646665832</ClientId>
            <SaveInSentItems/>
            <Mime>triggerException</Mime></SendMail>'
        );
        
        $sendMail = new Syncroton_Command_SendMail($doc, $this->_device, null);
        $sendMail->handle();
        $responseDoc = $sendMail->getResponse();
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ComposeMail', 'uri:ComposeMail');
        
        $nodes = $xpath->query('//ComposeMail:SendMail/ComposeMail:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Exception_Status::MAILBOX_SERVER_OFFLINE, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
    }
}
