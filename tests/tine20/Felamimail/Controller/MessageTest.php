<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
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
        $this->_cache      = Felamimail_Controller_Cache_Message::getInstance();
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
            echo "Remove message $message->subject" . PHP_EOL;
            $this->_controller->delete($message);
        }
    }

    /********************************* test funcs *************************************/
    
    /**
     * test adding a message
     */
    public function testAddMessage()
    {
        $testMessage = new Felamimail_Model_Message(array(
            'subject'       => 'PHPUnit test message',
            'messageuid'    => 987654321,
            'folder_id'     => $this->_getFolder()->getId(),
            'timestamp'     => Zend_Date::now(),
            'received'      => new Zend_Date('Fri, 6 Mar 2009 20:00:36 +0100', Zend_Date::RFC_2822, 'en_US'),
            'size'          => 30,
            'flags'         => array('\Seen'),
            'structure'     => array(1,2,3,4)
        ));
        $message = $this->_controller->create($testMessage);
        
        $this->_createdMessages[] = $message;

        $this->assertEquals($testMessage->structure, $message->structure);
    }

    /**
     * test getting multiple messages
     */
    public function testGetMultipleMessages()
    {
        $testMessage1 = new Felamimail_Model_Message(array(
            'subject'       => 'PHPUnit test message 1',
            'messageuid'    => 987654321,
            'folder_id'     => $this->_getFolder()->getId(),
            'timestamp'     => Zend_Date::now(),
            'received'      => new Zend_Date('Fri, 6 Mar 2009 20:00:36 +0100', Zend_Date::RFC_2822, 'en_US'),
            'size'          => 30,
            'flags'         => array('\Seen'),
            'structure'     => array(1,2,3,4)
        ));
        $message1 = $this->_controller->create($testMessage1);
        $this->_createdMessages[] = $message1;
        
        $testMessage2 = new Felamimail_Model_Message(array(
            'subject'       => 'PHPUnit test message 2',
            'messageuid'    => 987654322,
            'folder_id'     => $this->_getFolder()->getId(),
            'timestamp'     => Zend_Date::now(),
            'received'      => new Zend_Date('Fri, 6 Mar 2009 20:00:36 +0100', Zend_Date::RFC_2822, 'en_US'),
            'size'          => 30,
            'flags'         => array('\Seen'),
            'structure'     => array(1,2,3,4)
        ));
        $message2 = $this->_controller->create($testMessage2);
        $this->_createdMessages[] = $message2;
        
        $messages = $this->_controller->getMultiple(array(
            $message1->getId(),
            $message2->getId()
        ));
        
        $this->assertEquals($messages[0]->structure, $testMessage1->structure);
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
        $folder = $folderBackend->getByBackendAndGlobalName($this->_account->getId(), 'INBOX');
        
        // clear cache and empty folder
        Felamimail_Controller_Cache_Message::getInstance()->clear($folder->getId());
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
        
        // append message
        $this->_appendMessage('text_plain.eml', 'INBOX');
        
        // search messages in inbox
        $folder = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $folder->getId())->getFirstRecord();
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
    
    /**
     * test multipart alternative mail
     *
     */
    public function testMultipartAlternative()
    {
        $message = $this->_messageTestHelper('multipart_alternative.eml');
        
        // do checks
        $this->assertEquals('Kondome im Test: Fast schon perfekt', $message->subject);
        $this->assertEquals('<newsletter@stiftung-warentest.de>', $message->from);
        
        $completeMessage = $this->_controller->getCompleteMessage($message->getId());
        
        //print_r($completeMessage->toArray());
        $this->assertGreaterThan(
            0, 
            preg_match(
                "/Kondome sind der sicherste Schutz vor dem HI \(Humanes Immundefizienz\)\-Virus\. Absolut zuverlässig, urteilte die Stiftung Warentest schon 2004/", 
                $completeMessage->body
            ),
            'Text not found'
        );
        $this->assertGreaterThan(
            0, 
            preg_match(
                //'/E\-Mail:     <a href="mailto:email@stiftung\-warentest\.de"/',
                '/E\-Mail:     <a href="#" id="123:email@stiftung\-warentest\.de" class="tinebase\-email\-link">email@stiftung\-warentest\.de<\/a>/',
                $completeMessage->body
            ),
            'Email not found'
        );
        $this->assertGreaterThan(
            0, 
            preg_match(
                "/Sie können Ihr Newsletter-Abonnement selbst konfigurieren \.\.\./",
                $completeMessage->body
            ),
            'encoding not working'
        );
        $this->assertEquals('multipart/alternative; boundary="=_m192h4woyec67braywzx"', $completeMessage->headers['content-type']);
        
        // delete message
        $this->_controller->delete($message->getId());
    }
    
    /**
     * test multipart mixed mail
     *
     */
    public function testMultipartMixed()
    {
        $message = $this->_messageTestHelper('multipart_mixed.eml');
        
        // do checks
        $this->assertEquals('[gentoo-dev] Automated Package Removal and Addition Tracker, for the week ending 2009-04-12 23h59 UTC', $message->subject);
        $this->assertEquals('"Robin H. Johnson" <robbat2@gentoo.org>', $message->from);
        
        $completeMessage = $this->_controller->getCompleteMessage($message->getId(), TRUE);
        
        //print_r($completeMessage->toArray());
        $attachments = $completeMessage->attachments;
        $this->assertGreaterThan(
            0, 
            count($attachments),
            'attachments not found'
        );
        $this->assertEquals('multipart/mixed; boundary="0F1p//8PRICkK4MWrobbat28989323553773"', $completeMessage->headers['content-type']);
        $this->assertEquals('add-removals.1239580800.log', $attachments[1]['filename']);

        // delete message
        $this->_controller->delete($message->getId());
    }

    /**
     * test multipart signed mail
     *
     */
    public function testMultipartSigned()
    {
        $message = $this->_messageTestHelper('multipart_signed.eml');
        
        // do checks
        $this->assertEquals('[gentoo-dev] Last rites: dev-php5/pecl-zip', $message->subject);
        $this->assertEquals('Christian Hoffmann <hoffie@gentoo.org>', $message->from);
        
        $completeMessage = $this->_controller->getCompleteMessage($message->getId(), TRUE);
        
        $attachments = $completeMessage->attachments;
        
        // do checks
        $this->assertGreaterThan(
            0, 
            count($attachments),
            'attachments not found'
        );
        $this->assertEquals('multipart/signed; micalg=pgp-sha1; protocol="application/pgp-signature"; boundary=------------enig43E7BAD372988B39EC5ECE0B', $completeMessage->headers['content-type']);
        $this->assertEquals('signature.asc', $attachments[0]['filename']);
        $this->assertEquals('# Christian Hoffmann &lt;<a href="#" id="123:hoffie@gentoo.org" class="tinebase-email-link">hoffie@gentoo.org</a>&gt; (12 Apr 2009)
# Masked for security (bug 265756), unmaintained upstream (last release
# two years ago), will be removed in 30 days. Use dev-lang/php with
# USE=zip as a replacement, which is actively maintained and has more
# features.
dev-php5/pecl-zip

<a href="http://bugs.gentoo.org/show_bug.cgi?id=265756" target="_blank">http://bugs.gentoo.org/show_bug.cgi?id=265756</a>

-- 
Christian Hoffmann


', $completeMessage->body);
        
        // delete message
        $this->_controller->delete($message->getId());
    }
    

    /**
     * test mail with leading spaces
     *
     */
    public function testLeadingSpaces()
    {
        $message = $this->_messageTestHelper('leading_spaces.eml');
        $completeMessage = $this->_controller->getCompleteMessage($message->getId(), TRUE);
        
        //echo 'subject: ' . $message->subject . "\n";
        //echo 'from: ' . $message->from . "\n";
        //echo $completeMessage->body;
        
        // do checks
        $this->assertEquals('Ihre jajajaja über die xxxx einer stillen gefolgschaft', $message->subject);
        $this->assertEquals('textanwälte . berater . anwälte ) <someone@domain.org>', $message->from);
        $this->assertEquals("content\n", $completeMessage->body);
        
        // delete message
        $this->_controller->delete($message->getId());
    }
    
    public function testBodyStructureTextPlain()
    {
        $expectedStructure = array(
            'partId'      => null,
            'contentType' => 'TEXT/PLAIN',
            'type'        => 'TEXT',
            'subType'     => 'PLAIN',
            'parameters'  => array (
                'charset' => 'iso-8859-1'
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
        
        $this->_appendMessage('text_plain.eml', 'INBOX');
        
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
            'contentType' => 'MULTIPART/ALTERNATIVE',
            'type'        => 'MULTIPART',
            'subType'     => 'ALTERNATIVE',
            'parts'       => array(
                1 => array(
                    'partId'      => 1,
                    'contentType' => 'TEXT/PLAIN',
                    'type'        => 'TEXT',
                    'subType'     => 'PLAIN',
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
                    'contentType' => 'TEXT/HTML',
                    'type'        => 'TEXT',
                    'subType'     => 'HTML',
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
        
        $this->_appendMessage('multipart_alternative.eml', 'INBOX');
        
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
    
    public function testGetBodyMultipartRelated()
    {
        $this->_appendMessage('multipart_related.eml', 'INBOX');
        $result = $this->_imap->search(array(
            'HEADER X-Tine20TestMessage multipart/related'
        ));
        $message = $this->_imap->getSummary($result[0]);
        
        $cachedMessage = $this->_cache->addMessage($message, $this->_getFolder());
        
        $this->_createdMessages[] = $cachedMessage;

        $body = $this->_controller->getMessageBody($cachedMessage, $cachedMessage->text_partid, 'text/plain');
        
        $this->assertContains('w=FCrde', $body);
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
        $folder = $folderBackend->getByBackendAndGlobalName($this->_account->getId(), 'INBOX');
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
                
        $this->_appendMessage($_filename, 'INBOX');
        
        // get inbox folder id
        Felamimail_Controller_Cache_Folder::getInstance()->update($this->_account->getId());
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName($this->_account->getId(), 'INBOX');
        
        // search messages in inbox
        $folder = Felamimail_Controller_Cache_Folder::getInstance()->updateStatus($this->_account->getId(), NULL, $folder->getId())->getFirstRecord();
        Felamimail_Controller_Cache_Message::getInstance()->update($folder);
        $result = $this->_controller->search($this->_getFilter($folder->getId()));
        
        //print_r($result->toArray());
        
        $this->assertTrue(! empty($result));
        
        // return result
        return $result->getFirstRecord();        
    }
    
    /**
     * append message (from given filename) to folder
     *
     * @param string $_filename
     * @param string $_folder
     */
    protected function _appendMessage($_filename, $_folder)
    {
        $mailAsString = file_get_contents(dirname(dirname(__FILE__)) . '/files/' . $_filename);
        Felamimail_Backend_ImapFactory::factory($this->_account->getId())
            ->appendMessage($mailAsString, $_folder);
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
    protected function _getFolder($_folderName = 'INBOX')
    {
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => '',),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $this->_account->getId())
        ));
        $result = Felamimail_Controller_Folder::getInstance()->search($filter);
        $folder = $result->filter('localname', $_folderName)->getFirstRecord();
        if (empty($folder)) {
            print_r($result->toArray()); 
            throw new Exception('folder not found');
        }

        return $folder;
    }
}
