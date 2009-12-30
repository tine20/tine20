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
        $this->_controller = Felamimail_Controller_Message::getInstance();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }

    /********************************* test funcs *************************************/
    
    /**
     * test search with cache
     * - test text_plain.eml message
     *
     */
    public function testSearchWithCache()
    {
        // get inbox folder id
        Felamimail_Controller_Cache::getInstance()->updateFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', 'INBOX');
        
        // clear cache and empty folder
        Felamimail_Controller_Cache::getInstance()->clear($folder->getId());
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
        
        // append message
        $this->_appendMessage('text_plain.eml', 'INBOX');
        
        // search messages in inbox
        Felamimail_Controller_Cache::getInstance()->updateMessages($folder);
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
        Felamimail_Controller_Cache::getInstance()->clear($folder->getId());
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
        Felamimail_Controller_Cache::getInstance()->updateFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', 'INBOX');
        Felamimail_Controller_Folder::getInstance()->emptyFolder($folder->getId());
                
        $this->_appendMessage($_filename, 'INBOX');
        
        // get inbox folder id
        Felamimail_Controller_Cache::getInstance()->updateFolders();
        $folderBackend = new Felamimail_Backend_Folder();
        $folder = $folderBackend->getByBackendAndGlobalName('default', 'INBOX');
        
        // search messages in inbox
        Felamimail_Controller_Cache::getInstance()->updateMessages($folder);
        $result = $this->_controller->search($this->_getFilter($folder->getId()));
        
        //print_r($result->toArray());
        
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
        Felamimail_Backend_ImapFactory::factory(Felamimail_Model_Account::DEFAULT_ACCOUNT_ID)
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
}
