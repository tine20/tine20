<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Controller_MessageTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Felamimail_Controller_MessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Controller_Message
     */
    protected $_controller = array();
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * keep track of created messages
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_createdMessages;
    
    /**
     * @var Felamimail_Backend_Imap
     */
    protected $_imap = NULL;
    
    /**
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_cache;
    
    /**
     * @var Felamimail_Model_Folder
     */
    protected $_folder = NULL;
    
    /**
     * name of the folder to use for tests
     * @var string
     */
    #protected $_testFolderName = 'INBOX';
    protected $_testFolderName = 'Junk';
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Message Controller Tests');
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
        $this->_account    = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        $this->_controller = Felamimail_Controller_Message::getInstance();  
        $this->_imap       = Felamimail_Backend_ImapFactory::factory($this->_account);
        $this->_imap->selectFolder($this->_testFolderName);
        $this->_cache      = Felamimail_Controller_Cache_Message::getInstance();
        $this->_folder     = $this->_getFolder($this->_testFolderName);
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        //echo "deleting messages: " . print_r($this->_createdMessages->getArrayOfIds(), TRUE);
        $this->_controller->addFlags($this->_createdMessages, array(Zend_Mail_Storage::FLAG_DELETED));
    }

    /********************************* test funcs *************************************/
    
    /**
     * test getting multiple messages
     */
    public function testGetMultipleMessages()
    {
        $message1 = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');
        $message2 = $this->_messageTestHelper('text_plain.eml', 'text/plain');
        
        $messages = $this->_controller->getMultiple(array(
            $message1->getId(),
            $message2->getId()
        ));
        
        $this->assertEquals(2, count($messages));
    }
    
    /**
     * test search with cache
     * - test text_plain.eml message
     *
     */
    public function testSearchWithCache()
    {
        // get inbox folder id
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $this->_testFolderName);
        
        // clear cache and empty folder
        $this->_cache->clear($folder->getId());
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
        
        // append message
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        // search messages in test folder
        $this->_cache->updateCache($folder);
        $result = $this->_controller->search($this->_getFilter($folder->getId()));
        
        //print_r($result->toArray());
        
        // check result
        $firstMessage = $result->getFirstRecord();
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($folder->getId(), $firstMessage->folder_id);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $firstMessage->subject);
        
        // check cache entries
        $cacheBackend = new Felamimail_Backend_Cache_Sql_Message();
        $cachedMessage = $cacheBackend->get($firstMessage->getId());
        $this->assertEquals($folder->getId(), $cachedMessage->folder_id);
        $this->assertEquals(Zend_Date::now()->toString('YYYY-MM-dd'), $cachedMessage->timestamp->toString('YYYY-MM-dd'));
        
        // delete message
        $this->_controller->delete($firstMessage->getId());
        
        // clear cache
        $this->_cache->clear($folder->getId());
    }
    
    /**
     * testBodyStructureTextPlain
     */
    public function testBodyStructureTextPlain()
    {
        $expectedStructure = array(
            'partId'      => 1,
            'contentType' => 'text/plain',
            'type'        => 'text',
            'subType'     => 'plain',
            'parameters'  => array (
                'charset' => 'ISO-8859-1'
            ),
            'id'          => '', 
            'description' => '',
            'encoding'    => '7bit',
            'size'        => 388,
            'lines'       => 18,
            'disposition' => '',
            'language'    => '',
            'location'    => '',
            
        );

        $message = $this->_messageTestHelper('text_plain.eml', 'text/plain');
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
    /**
     * testBodyStructureMultipartAlternative
     */
    public function testBodyStructureMultipartAlternative()
    {
        $expectedStructure = array(
            'partId'      => null,
            'contentType' => 'multipart/alternative',
            'type'        => 'multipart',
            'subType'     => 'alternative',
            'parts'       => array(
                1 => array(
                    'partId'      => 1,
                    'contentType' => 'text/plain',
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'iso-8859-1'
                    ),
                    'id'          => '', 
                    'description' => '',
                    'encoding'    => 'quoted-printable',
                    'size'        => 1726,
                    'lines'       => 50,
                    'disposition' => '',
                    'language'    => '',
                    'location'    => '',
                ),
                2 => array(
                    'partId'      => 2,
                    'contentType' => 'text/html',
                    'type'        => 'text',
                    'subType'     => 'html',
                    'parameters'  => array (
                        'charset' => 'iso-8859-1'
                    ),
                    'id'          => '', 
                    'description' => '',
                    'encoding'    => 'quoted-printable',
                    'size'        => 10713,
                    'lines'       => 173,
                    'disposition' => '',
                    'language'    => '',
                    'location'    => '',
                )
            ),
            'parameters'  => array (
                'boundary' => '=_m192h4woyec67braywzx'
            ),
            'disposition' => '',
            'language'    => '',
            'location'    => '',
            
        );
        
        $message = $this->_messageTestHelper('multipart_alternative.eml', 'multipart/alternative'); 
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
    /**
     * testBodyStructureMultipartMixed
     */
    public function testBodyStructureMultipartMixed()
    {
        $expectedStructure = array(
            'partId'      => null,
            'contentType' => 'multipart/mixed',
            'type'        => 'multipart',
            'subType'     => 'mixed',
            'parts'       => array(
                1 => array(
                    'partId'      => 1,
                    'contentType' => 'text/plain',
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'us-ascii'
                    ),
                    'id'          => null, 
                    'description' => null,
                    'encoding'    => '7bit',
                    'size'        => 3896,
                    'lines'       => 62,
                    'disposition' => array(
                        'type'    => 'inline'
                    ),
                    'language'    => '',
                    'location'    => '',
                ),
                2 => array(
                    'partId'      => 2,
                    'contentType' => 'text/plain',
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'us-ascii'
                    ),
                    'id'          => '', 
                    'description' => '',
                    'encoding'    => '7bit',
                    'size'        => 2787,
                    'lines'       => 53,
                    'disposition' => array(
                        'type'    => 'attachment',
                        'parameters' => array(
                            'foobar'   => 'Test Subjäct',
                            'filename' => 'add-removals.1239580800.log'
                        )
                    ),
                    'language'    => '',
                    'location'    => '',
                )
            ),
            'parameters'  => array (
                'boundary' => '0F1p//8PRICkK4MWrobbat28989323553773'
            ),
            'disposition' => array(
                'type'    => 'inline'
            ),
            'language'    => '',
            'location'    => '',
            
        );
        
        $message = $this->_messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
    /**
     * testBodyStructureMultipartMixedWithMessageRFC822
     */
    public function testBodyStructureMultipartMixedWithMessageRFC822()
    {
        $expectedStructure = array(
            'partId'      => null,
            'contentType' => 'multipart/mixed',
            'type'        => 'multipart',
            'subType'     => 'mixed',
            'parts'       => array(
                1 => array(
                    'partId'      => 1,
                    'contentType' => 'text/plain',
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'ISO-8859-1',
                        'format'  => 'flowed'
                    ),
                    'id'          => null, 
                    'description' => null,
                    'encoding'    => '7bit',
                    'size'        => 49,
                    'lines'       => 5,
                    'disposition' => null,
                    'language'    => '',
                    'location'    => '',
                ),
                2 => array(
                    'partId'      => 2,
                    'contentType' => 'message/rfc822',
                    'type'        => 'message',
                    'subType'     => 'rfc822',
                    'parameters'  => array (
                        'name'    => '[Officespot-cs-svn] r15209 - trunk/tine20/Tinebase.eml'
                    ),
                    'id'          => '', 
                    'description' => '',
                    'encoding'    => '7bit',
                    'size'        => 4121,
                    'lines'       => null,
                    'disposition' => null,
                    'language'    => null,
                    'location'    => null,
                    'messageEnvelop' => array(
                        'Wed, 30 Jun 2010 13:20:09 +0200',
                        '[Officespot-cs-svn] r15209 - trunk/tine20/Tinebase',
                        array(array(
                            'NIL', 'NIL', 'c.weiss', 'metaways.de'
                        )),
                        array(array(
                            'NIL', 'NIL', 'c.weiss', 'metaways.de'
                        )),
                        array(array(
                            'NIL', 'NIL', 'c.weiss', 'metaways.de'
                        )),
                        array(array(
                            'NIL', 'NIL', 'officespot-cs-svn', 'lists.sourceforge.net'
                        )),
                        'NIL',
                        'NIL',
                        'NIL',
                        '<20100630112010.06CD21C059@publicsvn.hsn.metaways.net>'
                    ),
                    'messageStructure' => array(
                        'partId'  => 2,
                        'contentType' => 'text/plain',
                        'type'        => 'text',
                        'subType'     => 'plain',
                        'parameters'  => array (
                            'charset' => 'us-ascii'
                        ),
                        'id'          => null, 
                        'description' => null,
                        'encoding'    => '7bit',
                        'size'        => 1562,
                        'lines'       => 34,
                        'disposition' => null,
                        'language'    => '',
                        'location'    => '',
                    ),
                    'messageLines'    => 81,
                )
            ),
            'parameters'  => array (
                'boundary' => '------------040506070905080909080505'
            ),
            'disposition' => null,
            'language'    => '',
            'location'    => '',
            
        );
        
        $message = $this->_messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
    /**
     * testGetBodyMultipartRelated
     */
    public function testGetBodyMultipartRelated()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');

        $body = $this->_controller->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT);
        
        $this->assertContains('würde', $body);
    }
    
    /**
     * test reading a message without setting the \Seen flag
     */
    public function testGetBodyMultipartRelatedReadOnly()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');

        $body = $this->_controller->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT, true);
        
        $this->assertContains('würde', $body);
        
        // @todo check for seen flag
    }
    
    /**
     * testGetBodyPlainText
     */
    public function testGetBodyPlainText()
    {
        $cachedMessage = $this->_messageTestHelper('text_plain.eml', 'text/plain');
        
        $body = $this->_controller->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT);
        
        $this->assertContains('a converter script be written to', $body);
    }
    
    /**
     * testGetBodyPart
     */
    public function testGetBodyPart()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');
        
        $part = $this->_controller->getMessagePart($cachedMessage, '2');
        
        $this->assertContains(Zend_Mime::MULTIPART_RELATED, $part->type);
        $this->assertContains("------------080303000508040404000908", $part->boundary);
        
        $part = $this->_controller->getMessagePart($cachedMessage, '2.1');
        
        $this->assertContains(Zend_Mime::TYPE_HTML, $part->type);
        $this->assertContains(Zend_Mime::ENCODING_QUOTEDPRINTABLE, $part->encoding);
        
        $part = $this->_controller->getMessagePart($cachedMessage, '2.2');
        
        $this->assertContains(Zend_Mime::DISPOSITION_ATTACHMENT, $part->disposition);
        $this->assertContains(Zend_Mime::ENCODING_BASE64, $part->encoding);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        #var_dump($message->toArray());
        $this->assertEquals('1', $message->text_partid);
        $this->assertEquals('1', $message->has_attachment);
        $this->assertEquals(null, $message->html_partid);
        $this->assertEquals('9606', $message->size);
        $this->assertContains("Automated Package Removal", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertContains('11AC BA4F 4778 E3F6 E4ED  F38E B27B 944E 3488 4E85', $message->body);
        $this->assertEquals('add-removals.1239580800.log', $message->attachments[0]["filename"]);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage2()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        #var_dump($message->toArray());
        $this->assertEquals('1', $message->text_partid, 'no text part found');
        $this->assertEquals('1', $message->has_attachment, 'no attachments found');
        $this->assertEquals('2.1', $message->html_partid, 'no html part found');
        $this->assertEquals('38455', $message->size);
        $this->assertContains("Tine 2.0 bei Metaways", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertContains('Autovervollständigung', $message->body);
        $this->assertEquals('moz-screenshot-83.png', $message->attachments[0]["filename"]);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage3()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        $this->assertEquals('multipart/mixed', $message->content_type);
        $this->assertEquals('5377', $message->size);
        $this->assertContains("Fwd: [Officespot-cs-svn] r15209 - trunk/tine20/Tinebase", $message->subject);
        $this->assertContains('est for parsing forwarded email', $message->body);
        $this->assertEquals('message/rfc822', $message->attachments[0]["content-type"]);
    }

    /**
     * validate fetching a complete message from amazon
     */
    public function testGetCompleteMessageAmazon()
    {
        $cachedMessage = $this->_messageTestHelper('Amazon.eml', 'multipart/amazon');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        #var_dump($message->toArray());
        $this->assertEquals('multipart/alternative', $message->content_type);
        $this->assertContains('Samsung Wave S8500 Smartphone', $message->subject);
        $this->assertContains('Sie suchen Produkte aus der Kategorie Elektronik &amp; Foto?', $message->body);
    }
    
    /**
     * validate fetching a complete message (rfc2822 part) 
     */
    public function testGetMessageRFC822()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage, 2);
        $this->assertEquals('4121', $message->size);
        $this->assertContains("[Officespot-cs-svn] r15209 - trunk/tine20/Tinebase", $message->subject);
        $this->assertContains('getLogger()-&gt;debug', $message->body);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetMessageRFC822_2()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_rfc2822-2.eml', 'multipart/rfc2822-2');
        
        $message = $this->_controller->getCompleteMessage($cachedMessage, 2);
        #var_dump($message->toArray());
        #$this->assertEquals('1', $message->text_partid);
        #$this->assertEquals('2.1', $message->html_partid);
        $this->assertEquals('19131', $message->size);
        $this->assertContains("Proposal: Zend_Grid", $message->subject);
        #$this->assertContains('\Seen', $message->flags);
        $this->assertContains('Bento Vilas Boas wrote', $message->body ,'string not found in body: ' . $message->body);
        $this->assertEquals('smime.p7s', $message->attachments[0]["filename"]);
    }
    
    /**
     * test adding message with duplicate to: header
     */
    public function testAddMessageToCacheDuplicateTo()
    {
        $cachedMessage = $this->_messageTestHelper('text_plain2.eml', 'text_plain2.eml');
        
        $this->assertContains('c.weiss@metaways.de', $cachedMessage->to[0]['email'], 'wrong "to" header:' . print_r($cachedMessage->to, TRUE));
        $this->assertContains('online', $cachedMessage->subject);
    }
    
    /**
     * test adding message with empty date header
     */
    public function testAddMessageToCacheEmptyDate()
    {
        $cachedMessage = $this->_messageTestHelper('empty_date_header.eml', 'empty_date_header.eml');
        
        $this->assertEquals(0, $cachedMessage->sent->getTimestamp(), 'no timestamp should be set');
    }
    
    /**
     * test forward with attachment
     * 
     * @todo add encoding check / i.e. by comparing original and rfc822 msg
     */
    public function testForwardMessageWithAttachment()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');
        
        // forward message
        $forwardMessage = new Felamimail_Model_Message(array(
            'from'          => $this->_account->getId(),
            'subject'       => 'test forward',
            'to'            => array('unittest@tine20.org'),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822),
            'original_id'   => $cachedMessage->getId(),
            'attachments'   => array(array(
                'type'  => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name'  => $cachedMessage->subject,
            )),
        ));
        $this->_controller->sendMessage($forwardMessage);
        
        $forwardedMessage = $this->_searchAndCacheMessage(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, 'INBOX');
        $forwardedMessageInSent = $this->_searchAndCacheMessage(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, 'Sent');
        $completeForwardedMessage = $this->_controller->getCompleteMessage($forwardedMessage);
        
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $forwardedMessage['structure']['parts'][2]['contentType']);
        $this->assertEquals($cachedMessage->subject . '.eml', $forwardedMessage['structure']['parts'][2]['parameters']['name']);
        $this->assertEquals($cachedMessage->subject . '.eml', $completeForwardedMessage->attachments[0]['filename']);
    }    
    
    /**
     * testGetBodyPartIdMultipartAlternative
     */
    public function testGetBodyPartIdMultipartAlternative()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_alternative.eml', 'multipart/alternative');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
        $this->assertEquals(2, $cachedMessage->html_partid, 'did not find all partIds');
    }
        
    /**
     * testGetBodyPartIdMultipartMixed
     */
    public function testGetBodyPartIdMultipartMixed()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
    }
    
    /**
     * testGetBodyPartIdMultipartSigned
     */
    public function testGetBodyPartIdMultipartSigned()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_signed.eml', 'multipart/signed');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
    }
    
    /**
     * testGetBodyPartIdMultipartRelated
     */
    public function testGetBodyPartIdMultipartRelated()
    {
        $cachedMessage = $this->_messageTestHelper('multipart_related.eml', 'multipart/related');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
        $this->assertEquals('2.1', $cachedMessage->html_partid, 'did not find all partIds');
    }
            
    /********************************* protected helper funcs *************************************/
    
    /**
     * helper function
     * - appends message from file
     * - adds appended message to cache
     * 
     * @param string $_filename
     * @param string $_testHeaderValue
     * @return Felamimail_Model_Message
     */
    protected function _messageTestHelper($_filename, $_testHeaderValue)
    {
        $this->_appendMessage($_filename, $this->_folder);
        return $this->_searchAndCacheMessage($_testHeaderValue);
    }
    
    /**
     * search message by header (X-Tine20TestMessage) and add it to cache
     * 
     * @param string $_testHeaderValue
     * @param string $_folderName
     * @return Felamimail_Model_Message
     */
    protected function _searchAndCacheMessage($_testHeaderValue, $_folderName = NULL) 
    {
        if ($_folderName !== NULL) {
            $this->_imap->selectFolder($_folderName);
            $folder = $this->_getFolder($_folderName);
        } else {
            $this->_imap->selectFolder($this->_testFolderName);
            $folder = $this->_folder;
        }
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage ' . $_testHeaderValue
        ));
        $this->assertGreaterThan(0, count($result), 'No messages with HEADER X-Tine20TestMessage: ' . $_testHeaderValue . ' found.');
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $folder);
        $this->_createdMessages->addRecord($cachedMessage);
        
        return $cachedMessage;
    }
    
    /**
     * append message (from given filename) to cache
     *
     * @param string $_filename
     * @param string $_folder
     */
    protected function _appendMessage($_filename, $_folder)
    {
        $message = fopen(dirname(dirname(__FILE__)) . '/files/' . $_filename, 'r');
        $this->_controller->appendMessage($_folder, $message);
    }
    
    /**
     * get message filter
     *
     * @param string $_folderId
     * @return Felamimail_Model_MessageFilter
     */
    protected function _getFilter($_folderId)
    {
        return new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId)
        ));
    }
    
    /**
     * get folder
     *
     * @return Felamimail_Model_Folder
     */
    protected function _getFolder($_folderName = null)
    {
        $folderName = ($_folderName !== null) ? $_folderName : $this->_testFolderName;
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => '',),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        ));
        $result = Felamimail_Controller_Folder::getInstance()->search($filter);
        $folder = $result->filter('localname', $folderName)->getFirstRecord();
        if (empty($folder)) {
            //print_r($result->toArray()); 
            throw new Exception('folder not found');
        }

        return $folder;
    }
}
