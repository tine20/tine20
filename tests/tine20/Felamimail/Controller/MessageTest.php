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
     * @var array
     */
    protected $_createdMessages = array();
    
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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
        foreach($this->_createdMessages as $message) {
            #echo "Remove message $message->subject" . PHP_EOL;
            $this->_controller->delete($message);
        }
    }

    /********************************* test funcs *************************************/
    
    /**
     * test getting multiple messages
     */
    public function testGetMultipleMessages()
    {
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $message1 = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $message1;

        
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage text/plain'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $message2 = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $message2;
        
        
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
        Felamimail_Controller_Cache_Message::getInstance()->clear($folder->getId());
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
        
        // append message
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        // search messages in test folder
        Felamimail_Controller_Cache_Message::getInstance()->update($folder);
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
        Felamimail_Controller_Cache_Message::getInstance()->clear($folder->getId());
    }
    
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
        
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage text/plain'
        ));
        
        $this->assertGreaterThanOrEqual(1, count($result), 'no messages found matching HEADER X-Tine20TestMessage text/plain');
        
        $message = $this->_imap->getSummary($result[0]);
        
        foreach($result as $messageUid) {
            $this->_imap->removeMessage($messageUid);
        }
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
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
        
        $this->_appendMessage('multipart_alternative.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/alternative'
        ));
        
        $this->assertGreaterThanOrEqual(1, count($result), 'no messages found matching HEADER X-Tine20TestMessage multipart/alternative');
        
        $message = $this->_imap->getSummary($result[0]);
        
        foreach($result as $messageUid) {
            $this->_imap->removeMessage($messageUid);
        }
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
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
        
        $this->_appendMessage('multipart_mixed.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/mixed'
        ));
        
        $this->assertGreaterThanOrEqual(1, count($result), 'no messages found matching HEADER X-Tine20TestMessage multipart/alternative');
        
        $message = $this->_imap->getSummary($result[0]);
        
        foreach($result as $messageUid) {
            $this->_imap->removeMessage($messageUid);
        }
        
        $this->assertEquals($expectedStructure, $message['structure'], 'structure does not match');
    }
    
    public function testGetBodyMultipartRelated()
    {
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;

        $body = $this->_controller->getMessageBody($cachedMessage, Zend_Mime::TYPE_TEXT);
        
        $this->assertContains('würde', $body);
    }
    
    /**
     * test reading a message without setting the \Seen flag
     */
    public function testGetBodyMultipartRelatedReadOnly()
    {
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;

        $body = $this->_controller->getMessageBody($cachedMessage, Zend_Mime::TYPE_TEXT, true);
        
        $this->assertContains('würde', $body);
    }
    
    public function testGetBodyPlainText()
    {
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage text/plain'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;

        $body = $this->_controller->getMessageBody($cachedMessage, Zend_Mime::TYPE_TEXT);
        
        $this->assertContains('a converter script be written to', $body);
    }
    
    public function testGetBodyPart()
    {
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
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
    public function testGetCompleteMessage2()
    {
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        #var_dump($message);
        $this->assertEquals('1', $message->text_partid);
        $this->assertEquals('2.1', $message->html_partid);
        $this->assertEquals('38455', $message->size);
        $this->assertContains("Tine 2.0 bei Metaways", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertContains('Autovervollständigung', $message->body);
        $this->assertEquals('moz-screenshot-83.png', $message->attachments[0]["filename"]);
    }

    /**
     * test forward with attachment
     * 
     * @todo implement
     */
    public function testForwardMessageWithAttachment()
    {
        /*
        $this->_appendMessage('multipart_related.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        */
        // forward message
        /*
        $message = set original id = $cachedMessage->getId()
        $this->_controller->sendMessage($message)
        */
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage()
    {
        $this->_appendMessage('multipart_mixed.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/mixed'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
        $message = $this->_controller->getCompleteMessage($cachedMessage);
        
        $this->assertEquals('1', $message->text_partid);
        $this->assertEquals(null, $message->html_partid);
        $this->assertEquals('9606', $message->size);
        $this->assertContains("Automated Package Removal", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertContains('11AC BA4F 4778 E3F6 E4ED  F38E B27B 944E 3488 4E85', $message->body);
        $this->assertEquals('add-removals.1239580800.log', $message->attachments[0]["filename"]);
    }
    
    public function testAddMessageToCache()
    {
        $this->_appendMessage('text_plain.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage text/plain'
        ));
        
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
        $this->assertContains('gentoo-dev@lists.gentoo.org', $cachedMessage->to[0]['email']);
    }
    
    /**
     * test adding message with duplicate to: header
     */
    public function testAddMessageToCache2()
    {
        $this->_appendMessage('text_plain2.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage text_plain2.eml'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
        $this->assertContains('c.weiss@metaways.de', $cachedMessage->to[0]['email']);
        $this->assertContains('online', $cachedMessage->subject);
    }
    
    /**
     * test adding message with empty date header
     */
    public function testAddMessageToCache3()
    {
        $this->_appendMessage('empty_date_header.eml', $this->_folder);
        
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage empty_date_header.eml'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_folder);
        
        $this->_createdMessages[] = $cachedMessage;
        
        $this->assertEquals(0, $cachedMessage->sent->getTimestamp());
    }
    
    /**
     * test some mail
     *
     */
    public function testMessage()
    {
        /*
        $message = $this->_messageTestHelper('forwarded.eml');
        $completeMessage = $this->_controller->getCompleteMessage($message->getId(), TRUE);
        
        echo 'subject: ' . $message->subject . "\n";
        echo 'from: ' . $message->from . "\n";
        echo $completeMessage->body;
        
        //$attachments = $completeMessage->attachments;
        
        // do checks
        //$this->assertEquals('[gentoo-dev] Last rites: dev-php5/pecl-zip', $message->subject);
        //$this->assertEquals('Christian Hoffmann <hoffie@gentoo.org>', $message->from);
        //$this->assertEquals('multipart/signed; micalg=pgp-sha1; protocol="application/pgp-signature"; boundary=------------enig43E7BAD372988B39EC5ECE0B', $completeMessage->headers['content-type']);
        //$this->assertEquals('signature.asc', $attachments[0]['filename']);
        //$this->assertEquals('', $completeMessage->body);
        
        // delete message
        $this->_controller->delete($message->getId());
        */
    }
    
    /********************************* protected helper funcs *************************************/
    
    protected function _messageTestHelper($_filename)
    {
        // get inbox folder id and empty folder
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $this->_testFolderName);
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
                
        $this->_appendMessage($_filename, $this->_testFolderName);
        
        // get inbox folder id
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(), $this->_testFolderName);
        
        // search messages in inbox
        Felamimail_Controller_Cache_Message::getInstance()->update($folder);
        $result = $this->_controller->search($this->_getFilter($folder->getId()));
        
        //print_r($result->toArray());
        
        $this->assertTrue(! empty($result));
        
        // return result
        return $result->getFirstRecord();        
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
            print_r($result->toArray()); 
            throw new Exception('folder not found');
        }

        return $folder;
    }
}
