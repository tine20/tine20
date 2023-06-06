<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Felamimail_Controller_Message
 */
class Felamimail_Controller_MessageTest extends Felamimail_TestCase
{
    /**
     * @var Felamimail_Controller_Message
     */
    protected $_controller = null;
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = null;
    
    /**
     * keep track of created messages
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_createdMessages;
    
    /**
     * @var Felamimail_Backend_Imap
     */
    protected $_imap = null;
    
    /**
     * @var Felamimail_Controller_Cache_Message
     */
    protected $_cache;
    
    /**
     * @var Felamimail_Model_Folder
     */
    protected $_folder = null;
    
    /**
     * name of the folder to use for tests
     * @var string
     */
    protected $_testFolderName = 'Junk';
    
    /**
     * accounts to delete in tearDown
     *
     * @var array
     */
    protected $_accountsToDelete = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        $this->_account = TestServer::getInstance()->getTestEmailAccount();
        if (! $this->_account || $this->_account->type !== Felamimail_Model_Account::TYPE_SYSTEM) {
            throw new Tinebase_Exception('no valid test account found');
        }
        $this->_imap = Felamimail_Backend_ImapFactory::factory($this->_account);
        $this->_json = new Felamimail_Frontend_Json();

        $this->_folder = $this->getFolder($this->_testFolderName);
        try {
            $this->_imap->selectFolder($this->_testFolderName);
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Felamimail_Controller_Folder::getInstance()->create($this->_account, $this->_testFolderName);
        }
        $this->_cache = Felamimail_Controller_Cache_Message::getInstance();
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');

        if (Zend_Registry::isRegistered('personas')) {
            $this->_personas = Zend_Registry::get('personas');
        }
    }

    public function getAccount()
    {
        return $this->_account;
    }

    protected function _getController()
    {
        if (!$this->_controller) {
            $this->_controller = Felamimail_Controller_Message::getInstance();
        }

        return $this->_controller;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        try {
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($this->_createdMessages, array(Zend_Mail_Storage::FLAG_DELETED));
            if ($this->_createdMessages instanceof Tinebase_Record_RecordSet) {
                Felamimail_Controller_Message::getInstance()->delete($this->_createdMessages->getArrayOfIds());
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // do nothing
        }
        
        foreach ($this->_accountsToDelete as $account) {
            Felamimail_Controller_Account::getInstance()->delete($account);
        }
    }

    public static function tearDownAfterClass(): void
    {
        Felamimail_Backend_ImapFactory::reset();
        parent::tearDownAfterClass();
    }

    /********************************* test funcs *************************************/
    
    /**
     * test getting multiple messages
     * 
     * @group longrunning
     */
    public function testGetMultipleMessages()
    {
        $message1 = $this->messageTestHelper('multipart_related.eml', 'multipart/related');
        $message2 = $this->messageTestHelper('text_plain.eml', 'text/plain');
        
        $messages = $this->_getController()->getMultiple(array(
            $message1->getId(),
            $message2->getId()
        ));
        
        $this->assertEquals(2, count($messages));
    }
    
    /**
     * test search with cache
     * - test text_plain.eml message
     * - test from header
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
        $result = $this->_getController()->search($this->_getFilter($folder->getId()));
        
        //print_r($result->toArray());
        
        // check result
        $firstMessage = $result->getFirstRecord();
        $this->_createdMessages->addRecord($firstMessage);

        $this->assertGreaterThan(0, count($result));
        $this->assertEquals($folder->getId(), $firstMessage->folder_id);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $firstMessage->subject);
        $this->assertEquals('Pipping, Sebastian (Luxembourg)', $firstMessage->from_name);
        $this->assertEquals('webmaster@changchung.org', $firstMessage->from_email);
        $this->assertEquals(array('gentoo-dev@lists.gentoo.org', 'webmaster@changchung.org') , $firstMessage->to);
        
        // check cache entries
        $cacheBackend = new Felamimail_Backend_Cache_Sql_Message();
        $cachedMessage = $cacheBackend->get($firstMessage->getId());
        $this->assertEquals($folder->getId(), $cachedMessage->folder_id);
        $this->assertEquals(Tinebase_DateTime::now()->format('Y-m-d'), $cachedMessage->timestamp->format('Y-m-d'));
        
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
            'disposition' => '',
            'language'    => '',
            'location'    => '',
            
        );

        $message = $this->messageTestHelper('text_plain.eml', 'text/plain');
        
        $lines = $message['structure']['lines'];
        $structure = $message['structure'];
        unset($structure['lines']);
        
        $this->assertEquals($expectedStructure, $structure, 'structure does not match');
        // dbmail always has one more line than dovecot
        $this->assertTrue(in_array($lines, array(17, 18)));
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
        
        $message = $this->messageTestHelper('multipart_alternative.eml');
        $structure = $message['structure'];
        $lines = $this->_getLinesFromPartsAndRemoveFromStructure($structure);
        
        $this->assertEquals($expectedStructure, $structure, 'structure does not match');
        $this->assertTrue(in_array($lines[1], array(49, 50)));
        $this->assertTrue(in_array($lines[2], array(172, 173)));
    }
    
    /**
     * get lines from structure parts and remove them from structure array
     *
     * @param array $_structure
     * @return array
     */
    protected function _getLinesFromPartsAndRemoveFromStructure(&$_structure)
    {
        $lines = array();
        foreach ($_structure['parts'] as $key => $part) {
            $lines[$key] = $part['lines'];
            unset($_structure['parts'][$key]['lines']);
        }
        
        return $lines;
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
                    'contentType' => Felamimail_Model_Message::CONTENT_TYPE_PLAIN,
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'us-ascii'
                    ),
                    'id'          => null,
                    'description' => null,
                    'encoding'    => '7bit',
                    'size'        => 3896,
                    'disposition' => array(
                        'type'    => 'inline'
                    ),
                    'language'    => '',
                    'location'    => '',
                ),
                2 => array(
                    'partId'      => 2,
                    'contentType' => Felamimail_Model_Message::CONTENT_TYPE_PLAIN,
                    'type'        => 'text',
                    'subType'     => 'plain',
                    'parameters'  => array (
                        'charset' => 'us-ascii'
                    ),
                    'id'          => '',
                    'description' => '',
                    'encoding'    => '7bit',
                    'size'        => 2787,
                    'disposition' => array(
                        'type'    => 'attachment',
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
        
        $expectedParameters = array(
            'foobar'   => 'Test Subjäct',
            'filename' => 'add-removals.1239580800.log'
        );
        
        $message = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $structure = $message['structure'];
        $lines = $this->_getLinesFromPartsAndRemoveFromStructure($structure);
        // attachment parameters could have different order
        $parameters = $structure['parts'][2]['disposition']['parameters'];
        unset($structure['parts'][2]['disposition']['parameters']);
        
        $this->assertEquals($expectedStructure, $structure, 'structure does not match');
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_PLAIN, $message['body_content_type']);
        $this->assertTrue(in_array($lines[1], array(61, 62)));
        $this->assertTrue(in_array($lines[2], array(52, 53)));
        $this->assertTrue($expectedParameters == $parameters);
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
                        'disposition' => null,
                        'language'    => '',
                        'location'    => '',
                    ),
                )
            ),
            'parameters'  => array (
                'boundary' => '------------040506070905080909080505'
            ),
            'disposition' => null,
            'language'    => '',
            'location'    => '',
        );
        
        $message = $this->messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        $structure = $message['structure'];
        $lines = $this->_getLinesFromPartsAndRemoveFromStructure($structure);
        $lines[3] = $structure['parts'][2]['messageStructure']['lines'];
        $lines[4] = $structure['parts'][2]['messageLines'];
        unset($structure['parts'][2]['messageStructure']['lines']);
        unset($structure['parts'][2]['messageLines']);
        // remove disposition -> dbmail finds none, dovecot does
        $structure['parts'][2]['disposition'] = null;
        
        $this->assertEquals($expectedStructure, $structure, 'structure does not match');
        $this->assertTrue(in_array($lines[1], array(4, 5)));
        $this->assertEquals(null, $lines[2]);
        $this->assertTrue(in_array($lines[3], array(33, 34)));
        $this->assertTrue(in_array($lines[4], array(80, 81)));
    }
    
    /**
     * testGetBodyMultipartRelated
     */
    public function testGetBodyMultipartRelated()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');

        $body = $this->_getController()->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT, $this->_account);
        
        $this->assertStringContainsString('würde', $body);
    }
    
    /**
     * test reading a message without setting the \Seen flag
     */
    public function testGetBodyMultipartRelatedReadOnly()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');

        $body = $this->_getController()->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT, $this->_account, true);
        
        $this->assertStringContainsString('würde', $body);
        
        // @todo check for seen flag
    }
    
    /**
     * testGetBodyPlainText
     */
    public function testGetBodyPlainText()
    {
        $cachedMessage = $this->messageTestHelper('text_plain.eml', 'text/plain');
        
        $body = $this->_getController()->getMessageBody($cachedMessage, null, Zend_Mime::TYPE_TEXT, $this->_account);
        
        $this->assertStringContainsString('a converter script be written to', $body);
    }
    
    /**
     * testGetBodyPart
     */
    public function testGetBodyPart()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');
        
        $part = $this->_getController()->getMessagePart($cachedMessage, '2');
        
        $this->assertStringContainsString(Zend_Mime::MULTIPART_RELATED, $part->type);
        $this->assertStringContainsString("------------080303000508040404000908", $part->boundary);
        
        $part = $this->_getController()->getMessagePart($cachedMessage, '2.1');
        
        $this->assertStringContainsString(Zend_Mime::TYPE_HTML, $part->type);
        $this->assertStringContainsString(Zend_Mime::ENCODING_QUOTEDPRINTABLE, $part->encoding);
        
        $part = $this->_getController()->getMessagePart($cachedMessage, '2.2');
        
        $this->assertStringContainsString(Zend_Mime::DISPOSITION_ATTACHMENT, $part->disposition);
        $this->assertStringContainsString(Zend_Mime::ENCODING_BASE64, $part->encoding);
    }
    
    /**
     * testGetCompleteMessageAsPart
     */
    public function testGetCompleteMessageAsPart()
    {
        $cachedMessage = $this->messageTestHelper('complete.eml', 'text/service');
        
        $messagePart = $this->_getController()->getMessagePart($cachedMessage);
        
        ob_start();
        fpassthru($messagePart->getRawStream());
        $out = ob_get_clean();
        
        $this->assertStringContainsString('URL: https://service.metaways.net/Ticket/Display.html?id=3D59648', $out);
    }
        
    /**
     * testGetMessagePartRfc822
     */
    public function testGetMessagePartRfc822()
    {
        $cachedMessage = $this->messageTestHelper('multipart_rfc2822-2.eml', 'multipart/rfc2822-2');
        
        $messagePart = $this->_getController()->getMessagePart($cachedMessage, 2);
        
        ob_start();
        fpassthru($messagePart->getRawStream());
        $out = ob_get_clean();
        
        $this->assertStringContainsString('X-AntiAbuse: Originator/Caller UID/GID - [47 12] / [47 12]', $out, 'header not found');
        $this->assertStringContainsString('This component, from the feedback I have, will mostly be used on', $out, 'body not found');
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage()
    {
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertEquals('robbat2@gentoo.org', $message->from_email);
        $this->assertEquals($this->_account->getId(), $message->account_id);
        $this->assertEquals('Robin H. Johnsön', $message->from_name);
        $this->assertEquals('"Robin H. Johnsön" <robbat2@stork.gentoo.org>', $message->sender);
        $this->assertEquals('1', $message->text_partid);
        $this->assertEquals('1', $message->has_attachment);
        $this->assertEquals(null, $message->html_partid);
        $this->assertEquals('9636', $message->size);
        $this->assertStringContainsString("Automated Package Removal", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertStringContainsString('11AC BA4F 4778 E3F6 E4ED  F38E B27B 944E 3488 4E85', $message->body);
        $this->assertEquals('add-removals.1239580800.log', $message->attachments[0]["filename"]);
    }

    /**
     * validate fetching a complete message in 'other' dir and check its body
     *
     * howto:
     * - copy mails to tests/tine20/Felamimail/files/other
     * - add following header:
     *      X-Tine20TestMessage: _filename_
     * - run the test!
     */
    public function testCheckOtherMails()
    {
        $otherFilesDir = dirname(dirname(__FILE__)) . '/files/other';
        if (file_exists($otherFilesDir)) {
            foreach (new DirectoryIterator($otherFilesDir) as $item) {
                $filename = $item->getFileName();
                if ($item->isFile() && preg_match('/eml$/i', $filename)) {
                    $fileName = 'other/' . $filename;
                    echo "\nchecking message: " . $fileName . "\n";
                    $cachedMessage = $this->messageTestHelper($fileName, $filename);
                    $message = $this->_getController()->getCompleteMessage($cachedMessage);
                    $plainMessage = $this->_getController()->getCompleteMessage($message, null, Zend_Mime::TYPE_TEXT);
                    $this->assertTrue(! empty($message->body));
                    
                    echo "body: " . $message->body . "\n";
                    echo "plain body: " . $plainMessage->body . "\n";
                    echo "attachments: ";
                    print_r($message->attachments);

                    print_r($message->structure);
                }
            }
        }
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage2()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertEquals('1', $message->text_partid, 'no text part found');
        $this->assertEquals('1', $message->has_attachment, 'no attachments found');
        $this->assertEquals('2.1', $message->html_partid, 'no html part found');
        $this->assertTrue(in_array($message->size, array('38455', '38506')));
        $this->assertStringContainsString("Tine 2.0 bei Metaways", $message->subject);
        $this->assertContains('\Seen', $message->flags);
        $this->assertStringContainsString('Autovervollständigung', $message->body);
        $this->assertEquals('moz-screenshot-83.png', $message->attachments[0]["filename"]);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetCompleteMessage3()
    {
        $cachedMessage = $this->messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertEquals('multipart/mixed', $message->content_type);
        $this->assertEquals('5377', $message->size);
        $this->assertStringContainsString("Fwd: [Officespot-cs-svn] r15209 - trunk/tine20/Tinebase", $message->subject);
        $this->assertStringContainsString('est for parsing forwarded email', $message->body);
        $this->assertEquals('message/rfc822', $message->attachments[0]["content-type"]);
    }

    /**
     * validate fetching a complete message from amazon
     */
    public function testGetCompleteMessageAmazon()
    {
        $cachedMessage = $this->messageTestHelper('Amazon.eml', 'multipart/amazon');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertEquals('multipart/alternative', $message->content_type);
        $this->assertStringContainsString('Samsung Wave S8500 Smartphone', $message->subject);
        $this->assertStringContainsString('Sie suchen Produkte aus der Kategorie Elektronik &amp; Foto?', $message->body);
    }
    
    /**
     * validate fetching a message from yahoo
     *
     * test was created for task #4680
     */
    public function testGetCompleteMessageYahoo()
    {
        $cachedMessage = $this->messageTestHelper('yahoo.eml');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertStringContainsString('Bitte aktualisieren Sie Ihre Kontoeinstellungen bzw. Daten-Feeds so schnell wie möglich', $message->body);
    }
    
    /**
     * validate fetching a complete message from amazon #2 -> check if images got removed correctly
     */
    public function testGetCompleteMessageAmazon2()
    {
        $cachedMessage = $this->messageTestHelper('Amazon2.eml', 'multipart/amazon2');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('Fritz Meier, wir haben Empfehlungen', $message->body);
        $this->assertStringNotContainsString('<img', $message->body);
        $this->assertStringNotContainsString('style="background-image:url', $message->body);
        $this->assertStringNotContainsString('http://www.xing.com/img/xing/newsletter/navigation_bg.gif', $message->body);
    }
    
    /**
     * validate fetching a complete message from order form
     */
    public function testGetCompleteMessageOrder()
    {
        $cachedMessage = $this->messageTestHelper('Angebotsformular.eml', 'text/angebot');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertEquals('text/plain', $message->content_type);
        $this->assertStringContainsString('Angebotsformular', $message->subject);
        $this->assertStringContainsString('*Formular-Weiterleitungs-Service*', $message->body);
    }

    /**
     * validate fetching a complete message with different encodings
     */
    public function testGetCompleteMessageDifferentEncoding()
    {
        $cachedMessage = $this->messageTestHelper('UmlauteUTF8TextISO-8859-15Signatur.eml', 'text/different');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        //print_r($message->toArray());
        $this->assertEquals('text/plain', $message->content_type);
        $this->assertStringContainsString('Umlaute UTF8 Text + ISO-8859-15 Signatur', $message->subject);
        $this->assertStringContainsString('O Ö', $message->body);
    }
    
    /**
     * validate fetching a complete message (rfc2822 part)
     */
    public function testGetMessageRFC822()
    {
        $cachedMessage = $this->messageTestHelper('multipart_rfc2822.eml', 'multipart/rfc2822');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage, 2);
        
        $this->assertEquals('4121', $message->size);
        $this->assertStringContainsString("[Officespot-cs-svn] r15209 - trunk/tine20/Tinebase", $message->subject);
        $this->assertTrue(isset($message->body), 'no body found');
        $this->assertStringContainsString('getLogger()->debug', $message->body);
    }
    
    /**
     * validate fetching a complete message
     */
    public function testGetMessageRFC822_2()
    {
        $cachedMessage = $this->messageTestHelper('multipart_rfc2822-2.eml', 'multipart/rfc2822-2');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage, 2);
        
        $this->assertEquals('19131', $message->size);
        $this->assertStringContainsString("Proposal: Zend_Grid", $message->subject);
        $this->assertTrue(isset($message->body), 'no body found');
        $this->assertStringContainsString('Bento Vilas Boas wrote', $message->body ,'string not found in body: ' . $message->body);
        $this->assertEquals(1, count($message->attachments), 'did not find attachment');
        $this->assertEquals('smime.p7s', $message->attachments[0]["filename"]);
    }
    
    /**
     * validate fetching a complete message / rfc822 with base64
     */
    public function testGetMessageRFC822_3()
    {
        $cachedMessage = $this->messageTestHelper('multipart_rfc2822-3.eml', 'multipart/rfc2822-3');
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage, 2);
        
        $this->assertTrue(isset($message->body), 'no body found');
        $this->assertStringContainsString('this is base64 encoded', $message->body ,'string not found in body: ' . $message->body);
    }
    
    /**
     * test adding message with duplicate to: header
     */
    public function testAddMessageToCacheDuplicateTo()
    {
        $cachedMessage = $this->messageTestHelper('text_plain2.eml', 'text_plain2.eml');
        
        $this->assertGreaterThan(0, count($cachedMessage->to));
        $this->assertStringContainsString('c.weiss@metaways.de', $cachedMessage->to[0], 'wrong "to" header:' . print_r($cachedMessage->to, true));
        $this->assertStringContainsString('online', $cachedMessage->subject);
    }
    
    /**
     * test adding message with invalid date
     */
    public function testAddMessageToCacheInvalidDate()
    {
        $cachedMessage = $this->messageTestHelper('invaliddate.eml', 'text/invaliddate');
        
        $this->assertEquals('1970-01-01 00:00:00', $cachedMessage->sent->toString(), print_r($cachedMessage->toArray(), true));
    }
    
    /**
     * test adding message with another invalid date
     */
    public function testAddMessageToCacheInvalidDate2()
    {
        $cachedMessage = $this->messageTestHelper('invaliddate2.eml', 'text/invaliddate2');
        
        $this->assertEquals('2009-03-16 19:51:23', $cachedMessage->sent->toString());
    }

    /**
     * test adding message with another invalid date
     */
    public function testAddMessageToCacheInvalidDate3()
    {
        $cachedMessage = $this->messageTestHelper('invaliddate3.eml', 'text/invaliddate3');

        $this->assertEquals('1970-01-01 00:00:00', $cachedMessage->sent->toString());
    }

    /**
     * test adding message with empty date header
     */
    public function testAddMessageToCacheEmptyDate()
    {
        $cachedMessage = $this->messageTestHelper('empty_date_header.eml', 'empty_date_header.eml');
        
        $this->assertEquals(0, $cachedMessage->sent->getTimestamp(), 'no timestamp should be set');
    }
    
    /**
     * test forward with attachment
     * 
     * @group longrunning
     */
    public function testForwardMessageWithAttachments()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');
        $pdfFile = $this->_createTestNode(
            'newline.pdf',
            dirname(__FILE__, 3) . '/Tinebase/files/multipage-text.pdf'
        );
        $forwardMessage = new Felamimail_Model_Message(array(
            'account_id'    => $this->_account->getId(),
            'subject'       => 'test forward',
            'to'            => array($this->getEmailAddress()),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822),
            'original_id'   => $cachedMessage->getId(),
            'attachments'   => array(
                new Tinebase_Model_TempFile(array(
                    'type'  => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                    'name'  => $cachedMessage->subject,
                ), true),
                array(
                    'type' => 'file',
                    'attachment_type' => 'attachment',
                    'path' => $pdfFile[0]['path'],
                    'name' => $pdfFile[0]['name'],
                    'id' => $pdfFile[0]['id'],
                ),
            ),
        ));
        $sentFolder = $this->getFolder('Sent');

        Felamimail_Controller_Message_Send::getInstance()->sendMessage($forwardMessage);
        
        $forwardedMessage = $this->searchAndCacheMessage(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $this->getFolder('INBOX'));
        $forwardedMessageInSent = $this->searchAndCacheMessage(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $sentFolder);
        $completeForwardedMessage = $this->_getController()->getCompleteMessage($forwardedMessage);
        $attachmentName = preg_replace('/\s*/', '', $cachedMessage->subject);
        
        $this->assertEquals(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822, $forwardedMessage['structure']['parts'][2]['contentType']);
        $this->assertEquals($attachmentName . '.eml', $forwardedMessage['structure']['parts'][2]['parameters']['name'],
            'filename mismatch in structure' . print_r($forwardedMessage['structure']['parts'][2], true));
        $this->assertEquals($attachmentName . '.eml', $completeForwardedMessage->attachments[0]['filename'],
            'filename mismatch of attachment' . print_r($completeForwardedMessage->attachments[0], true));
        
        return $forwardedMessage;
    }

    /**
     * @see 0013618: Felamimail Message - introduce mass mailing and plugins for it
     */
    public function testPollMassMailingMessage()
    {
        $pollTest = new Calendar_Frontend_Json_PollTest();
        $pollTest->setUp();
        $oldTransport = Tinebase_Smtp::getDefaultTransport();
        $oldTestTransport = Felamimail_Transport::setTestTransport(null);
        static::resetMailer();

        try {
            Tinebase_Smtp::setDefaultTransport(new Felamimail_Transport_Array());
            Felamimail_Transport::setTestTransport(Tinebase_Smtp::getDefaultTransport());
            $event = $pollTest->testCreatePoll();

            $massMailingMessage = new Felamimail_Model_Message([
                'account_id' => $this->_account->getId(),
                'subject' => 'test poll mass mailing',
                'bcc' => [
                    [
                        "email" => Tinebase_Core::getUser()->accountEmailAddress,
                        "name" => '',
                        "type" =>  '',
                        "n_fileas" => '',
                        "email_type" =>  '',
                        "record_id" => ''
                    ],
                    $this->_personas['sclever']->accountEmailAddress
                ],
                'body' => '/Calendar/view/poll/' . $event['poll_id']['id'] . '/',
                'headers' => ['X-Tine20TestMessage' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822],
                'massMailingFlag' => true,
            ]);

            static::flushMailer();
            Felamimail_Controller_Message_Send::getInstance()->sendMessage($massMailingMessage);
            $messages = static::getMessages();
            static::assertEquals(2, count($messages), 'expected 2 mails send');

        } finally {
            Tinebase_Smtp::setDefaultTransport($oldTransport);
            Felamimail_Transport::setTestTransport($oldTestTransport);
            static::resetMailer();
            $pollTest->tearDown();
        }
    }

    /**
     * get email address
     *
     * @return string
     */
    public function getEmailAddress()
    {
        $config = TestServer::getInstance()->getConfig();
        $email = ($config->email) ? $config->email : Tinebase_Core::getUser()->accountEmailAddress;
        
        return $email;
    }

    /**
     * test forward message part
     * 
     * @group longrunning
     */
    public function testForwardMessagePart()
    {
        $forwardedMessage = $this->testForwardMessageWithAttachment();
        
        $forwardMessage = new Felamimail_Model_Message(array(
            'account_id'    => $this->_account->getId(),
            'subject'       => 'test forward part',
            'to'            => array($this->getEmailAddress()),
            'body'          => 'aaaaaä <br>',
            'headers'       => array('X-Tine20TestMessage' => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 . 'part'),
            'original_id'   => $forwardedMessage->getId() . '_2', // part 2 is the original forwared message
            'attachments'   => array(new Tinebase_Model_TempFile(array(
                'type'  => Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822,
                'name'  => $forwardedMessage->subject,
            ), true)),
        ));
        Felamimail_Controller_Message_Send::getInstance()->sendMessage($forwardMessage);
        
        $forwardedMessage = $this->searchAndCacheMessage(Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 . 'part', $this->getFolder('INBOX'));
        $completeForwardedMessagePart = $this->_getController()->getCompleteMessage($forwardedMessage, 2);
        
        //print_r($completeForwardedMessagePart->toArray());
        $this->assertTrue(! empty($completeForwardedMessagePart->headers), 'headers should not be empty');
        $this->assertEquals('moz-screenshot-83.png', $completeForwardedMessagePart->attachments[0]['filename']);
    }
    
    /**
     * testGetBodyPartIdMultipartAlternative
     */
    public function testGetBodyPartIdMultipartAlternative()
    {
        $cachedMessage = $this->messageTestHelper('multipart_alternative.eml');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
        $this->assertEquals(2, $cachedMessage->html_partid, 'did not find all partIds');
    }
        
    /**
     * testGetBodyPartIdMultipartMixed
     */
    public function testGetBodyPartIdMultipartMixed()
    {
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
    }
    
    /**
     * testGetBodyPartIdMultipartSigned
     */
    public function testGetBodyPartIdMultipartSigned()
    {
        $cachedMessage = $this->messageTestHelper('multipart_signed.eml', 'multipart/signed');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
    }
    
    /**
     * testGetBodyPartIdMultipartRelated
     */
    public function testGetBodyPartIdMultipartRelated()
    {
        $cachedMessage = $this->messageTestHelper('multipart_related.eml', 'multipart/related');
        $cachedMessage->parseBodyParts();

        $this->assertEquals(1, $cachedMessage->text_partid, 'did not find all partIds');
        $this->assertEquals('2.1', $cachedMessage->html_partid, 'did not find all partIds');
    }

    /**
     * testGetMessageWithoutFromHeader
     */
    public function testGetMessageWithoutFromHeader()
    {
        $cachedMessage = $this->messageTestHelper('withoutfrom.eml', 'text/withoutfrom');
        $completeMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('Hier ist Ihr Hot Web Email-Deal Angebot von M&amp;M Computer.', $completeMessage->body);
    }
    
    /**
     * testGetMessageWithCommaInTo
     */
    public function testGetMessageWithCommaInTo()
    {
        $cachedMessage = $this->messageTestHelper('mail_to_comma.eml', 'text/comma');
        $completeMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertEquals('inscription@arrakeen.net', $completeMessage->to[0]);
        $this->assertEquals('November 2010 Crystal Newsletter - Cut the Rope Update Released!', $completeMessage->subject);
    }
    
    /**
     * testUnparseableMail
     */
    public function testUnparseableMail()
    {
        self::markTestSkipped('FIXME: does not work anymore');

        $cachedMessage = $this->messageTestHelper('unparseable.eml', 'multipart/unparseable');
        $completeMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertEquals(1, preg_match('@NIL|Content-Type: image/jpeg@', $completeMessage->body),
            'parsed mail body:' . $completeMessage->body);
    }
    
    /**
     * test utf8 header decode
     */
    public function testUtf8HeaderDecode()
    {
        $cachedMessage = $this->messageTestHelper('decode_utf8_header.eml');
        $completeMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertEquals('"Jörn Meier" <j.meier@test.local>', $completeMessage->headers['reply-to']);
        $this->assertEquals('Jörn Meier <j.meier@test.local>', $completeMessage->headers['from']);
        $this->assertEquals('j.meier@test.local', $completeMessage->to[0]);
    }
    
    /**
     * testLongFrom
     */
    public function testLongFrom()
    {
        $cachedMessage = $this->messageTestHelper('longfrom.eml');
        $this->assertEquals('nDqIxSoSTIC', $cachedMessage->subject);
    }
    
    /**
     * testGetMessageWithQuotedPrintableDecodeProblem
     */
    public function testGetMessageWithQuotedPrintableDecodeProblem()
    {
        $cachedMessage = $this->messageTestHelper('Terminbestaetigung.eml', 'Terminbestaetigung.eml');
        $completeMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('Veröffentlichungen, Prospekte und Ähnliches bereithalten würden.', $completeMessage->body);
    }
    
    /**
     * test move to another account
     * 
     * @group longrunning
     */
    public function testMoveMessageToAnotherAccount()
    {
        $clonedAccount = $this->_cloneAccount();
        $folder = $this->getFolder('INBOX', $clonedAccount);
        
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $this->_moveTestHelper($cachedMessage, $folder);
    }
    
    /**
     * test move to another account (with message filter)
     * 
     * @group longrunning
     */
    public function testMoveMessageToAnotherAccountWithFilter()
    {
        $clonedAccount = $this->_cloneAccount();
        $folder = $this->getFolder('INBOX', $clonedAccount);
        
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed');
        $messageFilter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($cachedMessage->getId()))
        ));
        
        $this->_moveTestHelper($messageFilter, $folder);
    }
    
    /**
     * move message test helper
     *
     * @param mixed $_toMove
     * @param Felamimail_Model_Folder $_folder
     */
    protected function _moveTestHelper($_toMove, $_folder, $_keepOriginalMessage = false)
    {
        Felamimail_Controller_Folder::getInstance()->emptyFolder($_folder);
        Felamimail_Controller_Message_Move::getInstance()->moveMessages($_toMove, $_folder, $_keepOriginalMessage);
        $message = $this->_searchMessage('multipart/mixed', $_folder);
        
        $folder = $this->_cache->updateCache($_folder, 30, 10);
        while ($folder->cache_status === Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE) {
            $folder = $this->_cache->updateCache($folder, 30, 10);
        }
        $result = $this->_getController()->search($this->_getFilter($folder->getId()));
        foreach ($result as $messageInCache) {
            if ($messageInCache->messageuid == $message['uid']) {
                $foundMessage = $messageInCache;
                break;
            }
        }
        
        $this->assertTrue(isset($foundMessage));
        $this->_createdMessages[] = $foundMessage;
        $completeMessage = $this->_getController()->getCompleteMessage($foundMessage);
        $this->assertStringContainsString('The attached list notes all of the packages that were added or removed', $completeMessage->body);
    }
    
     /**
     * test delete in different accounts
     * 
     * @group longrunning
     */
    public function testDeleteMessagesInDifferentAccounts()
    {
        $clonedAccount = $this->_cloneAccount();
        
        $trashFolderMainAccount = $this->getFolder('Trash');
        $trashFolderClonedAccount = $this->getFolder('Trash', $clonedAccount);
        
        // empty trash
        Felamimail_Controller_Folder::getInstance()->emptyFolder($trashFolderMainAccount);
        
        $cachedMessage1 = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed', $trashFolderMainAccount);
        $cachedMessage2 = $this->messageTestHelper('complete.eml', 'text/service', $trashFolderClonedAccount);
        
        Felamimail_Controller_Message_Flags::getInstance()->addFlags(array($cachedMessage1->getId(), $cachedMessage2->getId()), array(Zend_Mail_Storage::FLAG_DELETED));
        
        $result1 = $this->_searchOnImap('multipart/mixed', $trashFolderMainAccount);
        $this->assertEquals(0, count($result1), $trashFolderMainAccount->globalname . ' still contains multipart/mixed messages:' . print_r($result1, true));
        $result2 = $this->_searchOnImap('text/service', $trashFolderClonedAccount);
        $this->assertEquals(0, count($result2), $trashFolderClonedAccount->globalname . ' still contains text/service messages:' . print_r($result2, true));
    }
    
    /**
     * test converting from punycode (xn--stermnn-9wa0n.org -> östermänn.org)
     */
    public function testPunycodedFromHeader()
    {
        $cachedMessage = $this->messageTestHelper('punycode_from.eml', 'punycode');
        $this->assertEquals('albert@östermänn.org', $cachedMessage->from_email);
    }

    /**
     * test converting to punycode
     */
    public function testEncodeToPunycode()
    {
        $message = new Felamimail_Model_Message(array(
            'to'        => array('albert@östermänn.org'),
            'subject'   => 'punycode test',
        ));
        $mail = Felamimail_Controller_Message_Send::getInstance()->createMailForSending($message, $this->_account);
        
        $recipients = $mail->getRecipients();
        $this->assertEquals('albert@xn--stermnn-9wa0n.org', $recipients[0]);
    }
    
    /**
     * test line end encoding of Zend_Mime_Part / Smtp Protocol
     */
    public function testSendWithWrongLineEnd()
    {
        // build message with wrong line end rfc822 part
        $mail = new Tinebase_Mail('utf-8');
        $mail->setBodyText('testmail' . "\r\n" . "\r\n");
        $mail->setFrom($this->_getEmailAddress(), 'unittest');
        $mail->setSubject('line end test');
        $mail->addTo($this->_getEmailAddress());
        $mail->addHeader('X-Tine20TestMessage', 'lineend');
        
        // replace EOLs
        $content = file_get_contents(dirname(dirname(__FILE__)) . '/files/text_plain.eml');
        $content = preg_replace("/\\x0a/", "\r\n", $content);
        $stream = fopen("php://temp", 'r+');
        fputs($stream, $content);
        rewind($stream);
        
        $attachment = new Zend_Mime_Part($stream);
        $attachment->type        = Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822;
        $attachment->encoding    =  null;
        $attachment->charset     = 'ISO-8859-1';
        $attachment->filename    = 'attach.eml';
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        
        $mail->addAttachment($attachment);
        
        $smtpConfig = $this->_account->getSmtpConfig();
        $transport = new Felamimail_Transport($smtpConfig['hostname'], $smtpConfig);
        Zend_Mail_Protocol_Abstract::$loggingEnabled = true;
        $mail->send($transport);
        Zend_Mail_Protocol_Abstract::$loggingEnabled = false;
        
        $smtpLog = $transport->getConnection()->getLog();
        
        $badLineEndCount = preg_match_all("/\\x0d\\x0d\\x0a/", $smtpLog, $matches);
        $this->assertEquals(0, $badLineEndCount);
        
        $unixLineEndCount = preg_match_all("/\\x0d/", $smtpLog, $matches);
        $this->assertEquals(1, $unixLineEndCount, 'only one unix line end should be found (got '
            . $unixLineEndCount . ' unix line ends)');
    }
    
   /**
    * validate email invitation
    */
    public function testEmailInvitation()
    {
        $email = $this->_getTestEmailAddress();
        $cachedMessage = $this->messageTestHelper('invitation.eml', null, null, array('unittest@tine20.org', $email));
        $this->_testInvitationMessage($cachedMessage, 'pwulf@tine20.org', 'testevent', 2);
    }
    
    /**
     * _testInvitationMessage
     * 
     * @param Felamimail_Model_Message $cachedMessage
     * @param string $expectedOriginator
     * @param string $expectedEventSummary
     * @param integer $expectedAttendeeCount
     */
    protected function _testInvitationMessage($cachedMessage, $expectedOriginator, $expectedEventSummary, $expectedAttendeeCount)
    {
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertEquals(1, count($message->preparedParts));
        $preparediMIPPart = $message->preparedParts->getFirstRecord()->preparedData;
        $this->assertTrue($preparediMIPPart instanceof Calendar_Model_iMIP, 'is no iMIP');
        $this->assertEquals($expectedOriginator, $preparediMIPPart->originator);
        $this->assertNotEmpty($preparediMIPPart->method, 'method is empty: ' . print_r($preparediMIPPart->toArray(), true));
        $event = $preparediMIPPart->getEvent();
        $this->assertTrue($event instanceof Calendar_Model_Event, 'is no event');
        $this->assertEquals($expectedEventSummary, $event->summary);
        $this->assertEquals($expectedAttendeeCount, count($event->attendee), 'attendee count mismatch: ' . print_r($event->attendee->toArray(), true));
    }

   /**
    * validate email invitation from mac
    */
    public function testEmailInvitationFromMac()
    {
        $cachedMessage = $this->messageTestHelper('mac_invitation.eml');
    
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
    
        $this->assertEquals(1, count($message->preparedParts));
        $preparediMIPPart = $message->preparedParts->getFirstRecord()->preparedData;
        $this->assertTrue($preparediMIPPart instanceof Calendar_Model_iMIP, 'is no iMIP');
        $this->assertEquals('pwulf@tine20.org', $preparediMIPPart->originator);
    }

   /**
    * validate email invitation from outlook
    * 
    * @see 0006110: handle iMIP messages from outlook
    */
    public function testEmailInvitationFromOutlook()
    {
        $email = $this->_getTestEmailAddress();
        $cachedMessage = $this->messageTestHelper('outlookimip.eml', null, null, array('name@example.net', $email));
        $this->_testInvitationMessage($cachedMessage, 'name@example.com', 'test', 1);
    }
    
   /**
    * validate email invitation from outlook (base64 encoded ics)
    * 
    * @see 0006110: handle iMIP messages from outlook
    */
    public function testEmailInvitationFromOutlookBase64()
    {
        $email = $this->_getTestEmailAddress();
        $cachedMessage = $this->messageTestHelper('invite_outlook.eml', null, null, array('oliver@example.org', $email));
        $this->_testInvitationMessage($cachedMessage, 'user@telekom.ch', 'Test von Outlook an Tine20', 1);
    }

    /**
     * validate email invitation from zoom
     */
    public function testEmailInvitationFromZoom()
    {
        $email = $this->_getTestEmailAddress();
        $cachedMessage = $this->messageTestHelper('zoom_invite.eml', null, null, array('name@example.net', $email));
        $this->_testInvitationMessage($cachedMessage, 'two@some.de', 'Klavierunterricht', 0);
    }

    /**
     * get test email address
     * 
     * @return string
     */
    protected function _getTestEmailAddress()
    {
        return $this->_getEmailAddress();
    }
    
    /**
     * testFromUTF8Encoding
     * 
     * @see 0006538: charset problems with recipients/senders
     */
    public function testFromUTF8Encoding()
    {
        $cachedMessage = $this->messageTestHelper('UTF8inFrom.eml');
        $this->assertEquals('Philipp Schüle', $cachedMessage->from_name, print_r($cachedMessage->toArray(), true));
    }
    
    /**
     * testHeaderWithoutEncodingInformation
     * 
     * @see 0006250: missing Umlauts in some mails
     */
    public function testHeaderWithoutEncodingInformation()
    {
        $cachedMessage = $this->messageTestHelper('Wortmann1.eml');
        
        $this->assertTrue(! empty($cachedMessage->subject) && is_string($cachedMessage->subject), 'subject empty or no string: '. print_r($cachedMessage->toArray(), true));
        $this->assertStringContainsString('Höchstgeschwindigkeit', $cachedMessage->subject, print_r($cachedMessage->toArray(), true));
    }
    
    /**
     * testFilterTooMuchHtml
     * 
     * @see 0007142: sometimes we filter to much html content
     */
    public function testFilterTooMuchHtml()
    {
        $cachedMessage = $this->messageTestHelper('heavyhtml.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertStringContainsString('unwahrscheinlichen Fall, dass Probleme auftreten sollten,', $message->body, print_r($message->toArray(), true));
    }
    
    /**
     * testUmlautAttachment
     * 
     * @see 0007624: losing umlauts in attached filenames
     */
    public function testUmlautAttachment()
    {
        $cachedMessage = $this->messageTestHelper('attachmentUmlaut.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertEquals(1, count($message->attachments));
        $this->assertEquals('äöppopä.txt', $message->attachments[0]['filename']);
    }

    /**
     * testNewsletterMultipartRelated
     * 
     * @see 0007722: improve handling of newsletters
     */
    public function testNewsletterMultipartRelated()
    {
        $cachedMessage = $this->messageTestHelper('mw_newsletter_multipart_related.eml');
        $this->assertEquals(1, $cachedMessage->has_attachment);
        $bodyParts = $cachedMessage->getBodyParts();
        $this->assertEquals(Zend_Mime::TYPE_HTML, $bodyParts['2.1']['contentType'], 'multipart/related html part missing: ' . print_r($bodyParts, true));
        
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringNotContainsString('----------------------------<br />TINE 2.0<br />-----------------------', $message->body, 'message body contains plain/text part');
        $this->assertStringContainsString('<p style="color:#999999;"><strong>Die Glühweinzeit hat bereits begonnen und kälter geworden ist es auch...</strong></p>', $message->body);
        $this->assertEquals(Zend_Mime::TYPE_HTML, $message->body_content_type);
    }

    /**
     * testNewsletterMultipartRelated
     * 
     * @see 0007858: could not parse structure of multipart/related msg
     */
    public function testMultipartRelatedAlternative()
    {
        $cachedMessage = $this->messageTestHelper('multipart_alternative_related.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        $this->assertStringContainsString('some body contentsome body contentsome body content', $message->body);
    }

    /**
     * testNoAttachment
     * 
     * @see 0008014: js client shows wrong attachment icon in grid
     */
    public function testNoAttachment()
    {
        $cachedMessage = $this->messageTestHelper('noattachment.eml');
        $this->assertEquals(0, $cachedMessage->has_attachment);
    }
    
    /**
     * testHtmlPurify
     * 
     * @see 0007726: show inline images of multipart/related message parts
     * 
     * @todo allow external resources
     *
     * @group nogitlabci
     * gitlabci: Failed asserting that 'Failed asserting that '...' contains "...".
     */
    public function testHtmlPurify()
    {
        $cachedMessage = $this->messageTestHelper('text_html_urls.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('<div></div>
    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="w38GIAXDIBKE0DHxgljNBAAO 9TXL0Y4OHwAAAABJRU5ErkJggg==" />
    
    <p>text</p>', $message->body);
    }

    /**
     * testNewsletterMultipartRelated
     * 
     * this tests if cid scheme src urls are replaced with Felamimail.getResource...
     * 
     * @see 0007726: show inline images of multipart/related message parts
     * 
     * @return Felamimail_Model_Message
     */
    public function testNewsletterMultipartRelatedWithImages()
    {
        $cachedMessage = $this->messageTestHelper('mw_newsletter_multipart_related.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('<img src="index.php?method=Felamimail.getResource&amp;cid=1354533197.50bc894dacd37@www.metaways.de&amp;messageId=' . $message->getId() . '"', $message->body);
        
        return $message;
    }
    
    /**
     * testGetResource
     * 
     * @see 0007726: show inline images of multipart/related message parts
     */
    public function testGetResource()
    {
        $message = $this->testNewsletterMultipartRelatedWithImages();
        
        $resourcePart = $this->_getController()->getResourcePartStructure('1354533197.50bc894dacd37@www.metaways.de', $message->getId());
        
        $this->assertEquals('2.3', $resourcePart['partId']);
    }
    
    /**
     * testFacebookNotification
     * 
     * @see 0008016: links in facebook/twitter emails are removed
     */
    public function testFacebookNotification()
    {
        $cachedMessage = $this->messageTestHelper('facebook_notification.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('http://www.facebook.com/n/?notifications&amp;id=295475095891&amp;'
            . 'mid=7a0ffadG5af33a8a9c98Ga61c449Gdd&amp;bcode=1.1362559617.Abl6w95TdWQc0VVS&amp;n_m=tine20%40metaways.de', $message->body);
    }

    /**
     * testBlockquoteClass
     * 
     * @see 0008574: add class "felamimail-body-blockquote" to all blockquote tags in mail body
     */
    public function testBlockquoteClass()
    {
        $cachedMessage = $this->messageTestHelper('blockquote.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringNotContainsString('<blockquote>', $message->body);
    }
    
    /********************************* protected helper funcs *************************************/
    
    /**
     * clones the account
     *
     * @return Felamimail_Model_Account
     */
    protected function _cloneAccount()
    {
        $account = clone($this->_account);
        unset($account->id);
        $this->_accountsToDelete[] = $account;
        $account = Felamimail_Controller_Account::getInstance()->create($account);
        
        return $account;
    }
    
    /**
     * helper function
     * - appends message from file
     * - adds appended message to cache
     *
     * @param string $_filename
     * @param string $_testHeaderValue
     * @param Felamimail_Model_Folder $_folder
     * @param array $_replacements
     * @return Felamimail_Model_Message
     */
    public function messageTestHelper($_filename, $_testHeaderValue = null, $_folder = null, $_replacements = array())
    {
        $testHeaderValue = ($_testHeaderValue !== null) ? $_testHeaderValue : $_filename;
        $folder = ($_folder !== null) ? $_folder : $this->_folder;
        $this->_appendMessage($_filename, $folder, $_replacements);
        return $this->searchAndCacheMessage($testHeaderValue, $folder);
    }
    
    /**
     * search message by header (X-Tine20TestMessage) and add it to cache
     *
     * @param string $_testHeaderValue
     * @param ?Felamimail_Model_Folder $_folder
     * @param boolean $assert
     * @param string $testHeader
     * @return ?Felamimail_Model_Message
     */
    public function searchAndCacheMessage($_testHeaderValue, $_folder = null, $assert = true, $testHeader = 'X-Tine20TestMessage'): ?Felamimail_Model_Message
    {
        $folder = ($_folder !== null) ? $_folder : $this->_folder;
        $message = $this->_searchMessage($_testHeaderValue, $folder, $assert, $testHeader);
        
        if ($message === null && ! $assert) {
            return null;
        }
        
        $cachedMessage = $this->_cache->addMessage($message, $folder);
        if ($cachedMessage === false) {
            // try to add message again (it had a duplicate)
            $this->_cache->clear($folder);
            $cachedMessage = $this->_cache->addMessage($message, $folder);
        }
        
        if ($assert) {
            self::assertTrue($cachedMessage instanceof Felamimail_Model_Message, 'could not add message to cache');
        }
        
        $this->_createdMessages->addRecord($cachedMessage);
        
        return $cachedMessage;
    }
    
    /**
     * search message in folder
     *
     * @param string $_testHeaderValue
     * @param Felamimail_Model_Folder $_folder
     * @param boolean $_assert
     * @param string $testHeader
     * @return array|null
     */
    protected function _searchMessage($_testHeaderValue, $_folder, $_assert = true, $testHeader = 'X-Tine20TestMessage')
    {
        $imap = $this->_getImapFromFolder($_folder);
        
        $count = 0;
        
        $result = $this->_searchOnImap($_testHeaderValue, $_folder, $imap, $testHeader);
        
        while (count($result) === 0 && $count++ < 5) {
            sleep(1);
            $result = $this->_searchOnImap($_testHeaderValue, $_folder, $imap, $testHeader);
        };
        
        if ($_assert) {
            $this->assertGreaterThan(0, count($result), 'No messages with HEADER "'
                . $testHeader . '": "' . $_testHeaderValue . '" in folder ' . $_folder->globalname . ' found.');
        }
        $message = (! empty($result)) ? $imap->getSummary($result[0]) : null;
        
        return $message;
    }
    
    /**
     * get imap backend
     *
     * @param Felamimail_Model_Folder $_folder
     * @return Felamimail_Backend_ImapProxy
     */
    protected function _getImapFromFolder($_folder) {
        if ($_folder->account_id == $this->_account->getId()) {
            $imap = $this->_imap;
        } else {
            $imap = Felamimail_Backend_ImapFactory::factory($_folder->account_id);
        }
        
        return $imap;
    }
    
    /**
     * search for messages on imap server
     *
     * @param string $_testHeaderValue
     * @param Felamimail_Model_Folder $_folder
     * @param Felamimail_Backend_ImapProxy $_imap
     * @param string $testHeader
     * @return array
     */
    protected function _searchOnImap($_testHeaderValue, $_folder, $_imap = null, $testHeader = 'X-Tine20TestMessage')
    {
        if ($_imap === null) {
            $imap = $this->_getImapFromFolder($_folder);
        } else {
            $imap = $_imap;
        }
        
        $imap->expunge($_folder->globalname);
        $result = $imap->search(array(
            'HEADER ' . $testHeader . ' ' . $_testHeaderValue
        ));
        
        return $result;
    }
    
    /**
     * append message (from given filename) to cache
     *
     * @param string $_filename
     * @param string $_folder
     * @param array $_replacements
     */
    protected function _appendMessage($_filename, $_folder, $_replacements = array())
    {
        $filename = dirname(dirname(__FILE__)) . '/files/' . $_filename;
        if (! empty($_replacements)) {
            $message = file_get_contents($filename);
            $message = preg_replace('/' . preg_quote($_replacements[0], '/') . '/m', $_replacements[1], $message);
        } else {
            $message = fopen($filename, 'r');
        }
        $this->_getController()->appendMessage($_folder, $message);
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
     * @return Felamimail_Model_Folder|null
     */
    public function getFolder($_folderName = null, $_account = null)
    {
        $folderName = ($_folderName !== null) ? $_folderName : $this->_testFolderName;
        $account = ($_account !== null) ? $_account : $this->_account;
        Felamimail_Controller_Cache_Folder::getInstance()->update($account);

        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'equals', 'value' => '',),
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())
        ));
        $result = Felamimail_Controller_Folder::getInstance()->search($filter);
        $folder = $result->filter('localname', $folderName)->getFirstRecord();
        if (empty($folder)) {
            $folder = Felamimail_Controller_Folder::getInstance()->create($account, $_folderName);
        }

        return $folder;
    }
    
    /**
     * test Tnef-Attachment (winmail.dat)
     * 
     * @see 0010076: Extract winmail.dat
     */
    public function testTnefAttachment()
    {
        if (! Tinebase_Core::systemCommandExists('tnef') && ! Tinebase_Core::systemCommandExists('ytnef')) {
            $this->markTestSkipped('The (y)tnef command could not be found!');
        }
        
        $cachedMessage = $this->messageTestHelper('winmail_dat_attachment.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
    
        $this->assertEquals(2, count($message->attachments), print_r($message->attachments, true));

        $bookmarkAttachments = array_filter($message->attachments, function ($attachment) {
            if ($attachment['filename'] === 'bookmark.htm') {
                return true;
            }
        });
        self::assertCount(1, $bookmarkAttachments, 'did not get bookmark.htm: '
            . print_r($message->attachments, true)
        );

        $path = Tinebase_Core::getTempDir() . '/winmail/' . $message->getId() . '/';
        $content = file_get_contents($path . 'bookmark.htm');
        
        $this->assertStringStartsWith('<!DOCTYPE NETSCAPE-Bookmark-file-1>', $content);
    }
    
    /**
     * test Tnef-Attachment (winmail.dat)
     *
     * @TODO: handle richtext encapsulated in tnef - this test assures, rtf-tnef won't produce any errors
     *
     * @see 0010076: Extract winmail.dat
     */
    public function testTnefRichtext()
    {
        if (! Tinebase_Core::systemCommandExists('tnef')) {
            $this->markTestSkipped('The tnef command could not be found!');
        }
        
        $cachedMessage = $this->messageTestHelper('winmail_dat_richtext.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
    
        self::assertCount(0, $message->attachments, 'winmail.dat should not be attached: ' . print_r($message->attachments, true));
    }
    
    /**
     * @see: 0010126: Text gets removed in invalid tags
     *       https://forge.tine20.org/mantisbt/view.php?id=10126
     *       
     *       http://htmlpurifier.org/live/configdoc/plain.html#HTML.TidyLevel
     */
    public function testInvalidHtml()
    {
        $cachedMessage = $this->messageTestHelper('invalid_html.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('hier seine Daten :)<br />', $message->body);
        $this->assertStringContainsString('<span id="felamimail-body-signature">', $message->body);
        $this->assertStringContainsString('</span><pre><span style="font-family:tahoma;">John Smith', $message->body);
    }

    /**
     * @see 0013150: convert single part file content body to attachment
     */
    public function testSinglePartPdfMail()
    {
        $cachedMessage = $this->messageTestHelper('single_part_pdf.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);

        self::assertEquals(1, count($message->attachments));
        self::assertTrue($message->has_attachment, 'attachments missing!');
    }

    /**
     * @see decoding of plain/text mail parts fails #75
     *      https://github.com/tine20/Tine-2.0-Open-Source-Groupware-and-CRM/issues/75
     */
    public function testBrokenPlainTextEncoding()
    {
        $cachedMessage = $this->messageTestHelper('branchenbuch2.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage, null, Zend_Mime::TYPE_TEXT);

        $this->assertStringContainsString('Sollten Sie zukünftig keine E-Mail Nachrichten empfangen wollen,'
            . ' senden sie bitte eine E-Mail mit dem Subject "OUT-MAIL" an info@', $message->body);
    }

    public function testBrokenEncodingInHeader()
    {
        $cachedMessage = $this->messageTestHelper('24706.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage, null, Zend_Mime::TYPE_TEXT);

        $this->assertStringContainsString('welche Private Krankenversicherung ist für mich die beste', $message->body);
    }

    public function testRewriteMessageSubject()
    {
        $cachedMessage = $this->messageTestHelper('24706.eml');
        $newSubject = 'i like my new subject';
        $rewrittenMessage = $this->_getController()->rewriteMessageSubject($cachedMessage, $newSubject);
        self::assertEquals($newSubject, $rewrittenMessage->subject);
        self::assertNotEquals($cachedMessage->messageuid, $rewrittenMessage->messageuid);
    }

    public function testRewriteMessageSubject2()
    {
        $cachedMessage = $this->messageTestHelper('multipart_alternative.eml');
        $completeCachedMessage = $this->_getController()->getCompleteMessage($cachedMessage);
        $newSubject = 'I like your new subject ' . Tinebase_Record_Abstract::generateUID(10);
        $rewrittenMessage = $this->_getController()->rewriteMessageSubject($cachedMessage, $newSubject);
        $this->_createdMessages->addRecord($rewrittenMessage);
   
        self::assertEquals($newSubject, $rewrittenMessage->subject);
        self::assertNotEquals($cachedMessage->messageuid, $rewrittenMessage->messageuid);

        // get updated cache message
        $messageBySubject = $this->_searchForMessageBySubject($newSubject, $this->_testFolderName);
        $rewrittenMessageFromCache = Felamimail_Controller_Message::getInstance()->getCompleteMessage($messageBySubject['id']);
        $this->_createdMessages->addRecord($rewrittenMessageFromCache);

        self::assertEquals($completeCachedMessage->body, $rewrittenMessageFromCache->body,
            'body mismatch: ' . print_r($rewrittenMessageFromCache->toArray(), true));
        self::assertEquals('2009-04-09 21:12:42', $rewrittenMessageFromCache->received->toString(),
            'received date mismatch: ' . print_r($rewrittenMessageFromCache->toArray(), true));
        self::assertEquals($completeCachedMessage->flags, $rewrittenMessageFromCache->flags,
            'flags mismatch: ' . print_r($rewrittenMessageFromCache->toArray(), true));

    }

    public function testNonMultipartStructure()
    {
        $cachedMessage = $this->messageTestHelper('non_multipart.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage, null, Zend_Mime::TYPE_TEXT);

        $this->assertStringContainsString('111111', $message->body);
        $this->assertEquals('text/html', $message->structure['contentType'], print_r($message->structure, true));
        $this->assertEquals(1, print_r($message->structure['partId'], true));
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testMessageUpdateWithTags()
    {
        $inbox = $this->_getFolder('INBOX');
        $cachedMessage = $this->messageTestHelper('multipart_alternative.eml', null, $inbox);
        $cachedMessage = $this->_testMessageAttachTags($cachedMessage, $inbox);
        $this->_testMessageDetachTags($cachedMessage, $inbox);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testMessageMoveWithTags()
    {
        $inbox = $this->_getFolder('INBOX');
        $targetFolder = $this->_getFolder('trash');
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed', $inbox);
        $cachedMessageWithTags = $this->_testMessageAttachTags($cachedMessage, $inbox);

        $this->_moveTestHelper($cachedMessageWithTags, $targetFolder);

        $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($cachedMessageWithTags);
        self::assertEquals(0, $tags->count());
        
        $result = $this->_getController()->search($this->_getFilter($targetFolder->getId()));
        // check if tags also updated?
        foreach ($result as $message) {
            $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($message);
            self::assertEquals($cachedMessageWithTags->flags, $message->flags, 'tags should be the same');
            self::assertEquals(1, $tags->count());
        }
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testMessageCopyWithTags()
    {
        $inbox = $this->_getFolder('INBOX');
        $targetFolder = $this->_getFolder('trash');
        $cachedMessage = $this->messageTestHelper('multipart_mixed.eml', 'multipart/mixed', $inbox);
        $cachedMessageWithTags = $this->_testMessageAttachTags($cachedMessage, $inbox);

        $this->_moveTestHelper($cachedMessageWithTags, $targetFolder, true);

        $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($cachedMessageWithTags);
        self::assertEquals(1, $tags->count());

        $result = $this->_getController()->search($this->_getFilter($targetFolder->getId()));
        // check if tags also updated?
        foreach ($result as $message) {
            $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($message);
            self::assertEquals($cachedMessageWithTags->flags, $message->flags, 'tags should be the same');
            self::assertEquals(1, $tags->count());
        }
    }

    /**
     * test delete in different accounts
     *
     * @group longrunning
     */
    public function testMessagesDeleteWithTags()
    {
        $inbox = $this->_getFolder('INBOX');
        $cachedMessage = $this->messageTestHelper('multipart_alternative.eml', null, $inbox);
        $this->_testMessageAttachTags($cachedMessage, $inbox);

        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($cachedMessage->getId()))
        ));

        // delete message
        $this->_getController()->delete($cachedMessage);

        $this->_cache->updateCache($inbox);
        $result = $this->_getController()->search($filter);

        foreach ($result as $message) {
            $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($message);
            $this->assertEquals(0, $tags->count());
            $this->assertEquals(count($cachedMessage->flags) + 1, count($message->flags), 'Tag not found in message ' . $message->subject);
        }
    }

    /**
     * test delete in different accounts
     *
     * @group longrunning
     */
    public function testMessagesDeleteByFolderWithTags()
    {
        $inbox = $this->_getFolder('INBOX');
        $filter = $this->_getFilter($inbox->getId());
        $cachedMessage = $this->messageTestHelper('multipart_alternative.eml', null, $inbox);

        $this->_testMessageAttachTags($cachedMessage, $inbox);

        // empty index
        Felamimail_Controller_Folder::getInstance()->emptyFolder($inbox);

        $this->_cache->updateCache($inbox);
        $result = $this->_getController()->search($filter);

        foreach ($result as $message) {
            $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($message);
            $this->assertEquals(0, $tags->count());
            $this->assertEquals(count($cachedMessage->flags) + 1, count($message->flags), 'Tag not found in message ' . $message->subject);
        }
    }

    /**
     * test message attach tags
     */
    public function _testMessageAttachTags($_cachedMessage, $_folder)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($_cachedMessage->getId()))
        ));

        $tagData = array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tag::testMessageAttachTags',
            'description' => 'testMessageAttachTags',
            'color' => '#009B31',
        );

        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tagData);
        $result = $this->_getController()->search($filter);

        foreach ($result as $message) {
            $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($message);
            $this->assertEquals(1, $tags->count());
            $this->assertEquals(count($_cachedMessage->flags) + 1, count($message->flags), 'Tag not found in contact ' . $message->subject);
        }
        
        return $result->getFirstRecord();
    }

    /**
     * test message detach tags
     * @throws Tinebase_Exception_AccessDenied
     */
    public function _testMessageDetachTags($_cachedMessage, $_folder)
    {
        $filter = $this->_getFilter($_folder->getId());

        $tagData1 = array(
            'type' => Tinebase_Model_Tag::TYPE_SHARED,
            'name' => 'tagMulti::test1',
            'description' => 'testMessageDetachTags',
            'color' => '#009B31',
        );
        $tag1 = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tagData1);

        $tagData2 = array(
            'type' => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name' => 'tagMulti::test2',
            'description' => 'testDetachTagToMultipleRecords2',
            'color' => '#ff9B31',
        );
        $tag2 = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tagData2);
        $result = $this->_getController()->search($filter);
        $tasgIds = [$tag1->getId(), $tag2->getId()];

        foreach ($result as $message) {
            $this->assertEquals(count($_cachedMessage->flags) + 2, count($message->flags), 'Tags not found in message ' . $message->subject);
        }

        // Try to remove the created Tags
        try {
            Tinebase_Tags::getInstance()->detachTagsFromMultipleRecords($filter, $tasgIds);
        } catch (Zend_Db_Statement_Exception $zdse) {
            $this->fail('failed to detach tags: ' . print_r($tasgIds, TRUE) . ' / exception: ' . $zdse);
        }

        $result = $this->_getController()->search($filter);

        foreach ($result as $message) {
            $this->assertEquals(count($_cachedMessage->flags), count($message->flags), 'Tags should not be found not found in message ' . $message->subject);
        }
    }
    
    /**
     * test Confirm Subscription Link
     *
     */
    public function testConfirmSubscription()
    {
        self::markTestSkipped('FIXME: htmlpurifier always remove conditional content');
        //see https://github.com/ezyang/htmlpurifier/blob/master/library/HTMLPurifier/Lexer.php#L277
        $cachedMessage = $this->messageTestHelper('subscription_notification.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);
        
        $this->assertStringContainsString('https://link11.statuspage.io/subscriptions/confirm/ooaCpodSniBI0T4i', $message->body);
    }

    /**
     * test mail with only html part
     *
     */
    public function testOnlyHtmlPart()
    {
        $cachedMessage = $this->messageTestHelper('only_html_part.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);

        $this->assertStringContainsString('10848255', $message->body);
    }

    /**
     * test mail with only html part
     *
     */
    public function testJoinTeamsMeeting()
    {
        $cachedMessage = $this->messageTestHelper('join_teams_meeting.eml');
        $message = $this->_getController()->getCompleteMessage($cachedMessage);

        $this->assertStringContainsString('<a href="https://teams.live.com/meet/9477691496180" target="_blank">Click here to join the meeting</a>', $message->body);
    }
}
