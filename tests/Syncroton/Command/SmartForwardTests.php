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
class Syncroton_Command_SmartForwardTests extends Syncroton_Command_ATestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('ActiveSync SmartForward command tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * test forwarding emails
     */
    public function testSmartForward()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <SmartForward xmlns="uri:ComposeMail">
                <ClientId>SendMail-1044646665832</ClientId>
                <SaveInSentItems/>
                <Source>
                    <ItemId>a7fb71114125cc569d09948988a92f9d31321656</ItemId>
                    <FolderId>a130b7462fde72c7d6215ce32226e1794d631fa8</FolderId>
                </Source>
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
</Mime>
            </SmartForward>'
        );
        
        $smartForward = new Syncroton_Command_SmartForward($doc, $this->_device, null);
        $smartForward->handle();
        $responseDoc = $smartForward->getResponse();
        
        $this->assertEquals(null, $responseDoc);
    }
    
    /**
     * test forwarding emails with LongId element
     */
    public function testSmartForwardWithLongId()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <SmartForward xmlns="uri:ComposeMail">
                <ClientId>SendMail-1044646665832</ClientId>
                <SaveInSentItems/>
                <Source>
                    <LongId>a7fb71114125cc569d09948988a92f9d31321656</LongId>
                </Source>
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
</Mime>
            </SmartForward>'
        );
        
        $smartForward = new Syncroton_Command_SmartForward($doc, $this->_device, null);
        $smartForward->handle();
        $responseDoc = $smartForward->getResponse();
        
        $this->assertEquals(null, $responseDoc);
    }
    
    /**
     * test forwarding emails failure
     */
    public function testSmartForwardException()
    {
        // delete folder created above
        $doc = new DOMDocument();
        $doc->loadXML('<!DOCTYPE AirSync PUBLIC "-//AIRSYNC//DTD AirSync//EN" "http://www.microsoft.com/">
            <SmartForward xmlns="uri:ComposeMail">
                <ClientId>SendMail-1044646665832</ClientId>
                <SaveInSentItems/>
                <Source>
                    <ItemId>a7fb71114125cc569d09948988a92f9d31321656</ItemId>
                    <FolderId>a130b7462fde72c7d6215ce32226e1794d631fa8</FolderId>
                </Source>
                <Mime>triggerException</Mime>
            </SmartForward>'
        );
        
        $smartForward = new Syncroton_Command_SmartForward($doc, $this->_device, null);
        $smartForward->handle();
        $responseDoc = $smartForward->getResponse();
        
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('ComposeMail', 'uri:ComposeMail');
        
        $nodes = $xpath->query('//ComposeMail:SmartForward/ComposeMail:Status');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertEquals(Syncroton_Exception_Status::MAILBOX_SERVER_OFFLINE, $nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
