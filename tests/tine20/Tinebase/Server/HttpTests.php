<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Http
 * 
 * @package     Tinebase
 */
class Tinebase_Server_HttpTests extends TestCase
{
    /**
     * @group ServerTests
     *
     * @see  0012364: generalize import/export and allow to configure via modelconfig
     */
    public function testHandleRequestForDynamicAPI()
    {
        $server = new Tinebase_Server_Http();

        $request = Tinebase_Http_Request::fromString(
'POST /index.php HTTP/1.1' . "\r\n"
. 'Host: localhost' . "\r\n"
. 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
. 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
. 'Accept: */*' . "\r\n"
. 'Referer: http://tine20.vagrant/' . "\r\n"
. 'Accept-Encoding: gzip, deflate' . "\r\n"
. 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
. "\r\n"
        );

        // set method & params
        $_REQUEST['method'] = 'ExampleApplication.exportExampleRecords';
        $_REQUEST['filter'] = Zend_Json::encode(array());
        $_REQUEST['options'] = Zend_Json::encode(array('format' => 'csv'));
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();
        //echo $out;

        $this->assertTrue(! empty($out), 'request should not be empty');
        $this->assertNotContains('Not Authorised', $out);
        $this->assertNotContains('Method not found', $out);
        $this->assertNotContains('No Application Controller found', $out);
        $this->assertNotContains('"error"', $out);
        $this->assertNotContains('PHP Fatal error', $out);
        $this->assertContains('"name","description","status","reason","number_str","number_int","datetime","relations","container_id","tags","attachments","notes","seq","tags"', $out);
    }

    /**
     * @group ServerTests
     *
     * @see  0012364: generalize import/export and allow to configure via modelconfig
     *
     * TODO create message first?
     */
    public function testHandleRequestForReflectionAPI()
    {
        $server = new Tinebase_Server_Http();

        $request = Tinebase_Http_Request::fromString(
            'POST /index.php HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        );

        // set method & params
        $_REQUEST['method'] = 'Felamimail.downloadAttachment';
        $_REQUEST['messageId'] = '1110de84c05316e55be87beab2ae5f0fb877b35f';
        $_REQUEST['partId'] = '1.1.2';
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();
        //echo $out;

        $this->assertTrue(empty($out), 'request should be empty - no message with this id + part should be found');
        $this->assertNotContains('Not Authorised', $out);
        $this->assertNotContains('Method not found', $out);
        $this->assertNotContains('No Application Controller found', $out);
        $this->assertNotContains('"error"', $out);
        $this->assertNotContains('PHP Fatal error', $out);
    }
}
