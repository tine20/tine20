<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for SendMail_Controller_Event
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_SendMailTests extends PHPUnit_Framework_TestCase
{
    /**
    * email test class for checking emails on IMAP server
    *
    * @var Felamimail_Controller_MessageTest
    */
    protected $_emailTestClass;
    
    /**
    * keep track of created messages
    *
    * @var Tinebase_Record_RecordSet
    */
    protected $_createdMessages;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync SendMail Command Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount) || $imapConfig->useSystemAccount != TRUE) {
            $this->markTestSkipped('IMAP backend not configured');
        }
        $this->_emailTestClass = new Felamimail_Controller_MessageTest();
        $this->_emailTestClass->setup();
        $this->_createdMessages = new Tinebase_Record_RecordSet('Felamimail_Model_Message');
        
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $testDevice = ActiveSync_Backend_DeviceTests::getTestDevice();
        
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->create($testDevice);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Felamimail_Controller_Message_Flags::getInstance()->addFlags($this->_createdMessages, array(Zend_Mail_Storage::FLAG_DELETED));
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test (plain text) mail sending via ActiveSync_Command_SendMail
     */
    public function testSendMail()
    {
        $email = file_get_contents(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml');
        $email = str_replace('gentoo-dev@lists.gentoo.org, webmaster@changchung.org', $this->_emailTestClass->getEmailAddress(), $email);
        $stream = fopen('data://text/plain;base64,' . base64_encode($email), 'r');
        
        $sendMail = new Syncope_Command_SendMail($stream);
        $sendMail->handle();
        $sendMail->getResponse();
        
        // check if mail is in INBOX of test account
        $inbox = $this->_emailTestClass->getFolder('INBOX');
        $testHeaderValue = 'text/plain';
        $message = $this->_emailTestClass->searchAndCacheMessage($testHeaderValue, $inbox);
        $this->_createdMessages->addRecord($message);
        $this->assertEquals("Re: [gentoo-dev] `paludis --info' is not like `emerge --info'", $message->subject);
        
        // check duplicate headers
        $completeMessage = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
        $this->assertEquals(1, count($completeMessage->headers['mime-version']));
        $this->assertEquals(1, count($completeMessage->headers['content-type']));
    }
}
