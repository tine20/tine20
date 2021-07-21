<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Felamimail_Model_MessageTest
 */
class Felamimail_Model_MessageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Felamimail Message Model Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }

    /********************************* test funcs *************************************/
    
    /**
     * test conversion to plain text (blockquotes to quotes) / tests linebreaks, too
     */
    public function testGetPlainTextBody()
    {
        $message = new Felamimail_Model_Message(array(
            'body'  =>  'blabla<br/><blockquote class="felamimail-body-blockquote">lalülüüla<br/><br/><div>lala</div><br/><blockquote class="felamimail-body-blockquote">xyz</blockquote></blockquote><br/><br/>jojo<br/>' .
                        'lkjlhk<div><br></div><div><br><div>jjlöjlö</div><div><font face="arial"><br></font></div></div><div><font face="arial">Pickhuben 2-4, 20457 Hamburg</font></div>'
        ));
        
        $result = $message->getPlainTextBody();
        //echo $result;
        
        $this->assertEquals("blabla\n" .
            "> lalülüüla\n" .
            "> \n" .
            "> lala\n" .
            "> \n" . 
            "> > xyz\n" .
            "\n\n" .
            "jojo\n" .
            "lkjlhk\n\n\n" .
            "jjlöjlö\n\n" .
            "Pickhuben 2-4, 20457 Hamburg\n", $result);
    }

    /**
     * test conversion from plain text to html (quotes ('> > ...') to blockquotes)
     * 
     * @see 0005334: convert plain text quoting ("> ") to html blockquotes
     */
    public function testTextToHtml()
    {
        $plaintextMessage = "blabla\n" .
            "> lalülüüla\n" .
            "> \n" .
            "> > >lala\n" .
            ">  >\n" . 
            ">  > xyz\n" .
            "\n\n" .
            "> jojo\n" .
            "jojo\n" ;
        
        $result = Tinebase_Mail::convertFromTextToHTML($plaintextMessage, 'felamimail-body-blockquote');
        
        $this->assertEquals('blabla<br /><blockquote class="felamimail-body-blockquote">lalülüüla<br /><br />'
            . '<blockquote class="felamimail-body-blockquote"><blockquote class="felamimail-body-blockquote">lala<br />'
            . '</blockquote><br />xyz<br /></blockquote></blockquote><br /><br /><blockquote class="felamimail-body-blockquote">jojo<br /></blockquote>jojo<br />', $result);
    }
    
    /**
     * testReplaceUris
     * 
     * @see 0008020: link did not get an anchor in html mail
     */
    public function testReplaceUrisAndMails()
    {
        $message = new Felamimail_Model_Message(array(
            'body'  =>  'http://www.facebook.com/media/set/?set=a.164136103742229.1073741825.100004375207149&type=1&l=692e495b17'
                . " Klicken Sie bitte noch auf den folgenden Link, um Ihre Teilnahme zu bestätigen:\n"
                . 'http://www.kieler-linuxtage.de/vortragsplaner/wsAnmeldung.php?fkt=best&wsID=111&code=xxxx&eMail=abc@efh.com'
                . '   &lt;http://my.serveer.com/job/job1/137/display/redirect?page=changes&gt;'
        ));
        
        $result = Felamimail_Message::replaceUris($message->body);
        $result = Felamimail_Message::replaceEmails($result);

        $this->assertStringContainsString('a href="http://www.facebook.com/media/set/', $result);
        $this->assertStringContainsString('a href="http://www.kieler-linuxtage.de/', $result);
        $this->assertStringContainsString('eMail=abc@efh.com', $result);
        $this->assertStringContainsString('a href="http://my.serveer.com/job/job1/137/display/redirect?page=changes"', $result);
    }

    /**
     * test spam suspicion subject strategy
     */
    public function testSpamSuspicionSubjectStrategy()
    {
        Felamimail_Config::getInstance()->set(Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY, TRUE);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');

        $config = [
            'pattern' => '/^SPAM\? \(.+\) \*\*\* /',
        ];

        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG, $config);

        $message = new Felamimail_Model_Message([
            'subject' => 'SPAM? (Score = 14.53 / 15) *** Super preise',
        ]);

        $strategy = Felamimail_Spam_SuspicionStrategy_Factory::factory();
        $message->is_spam_suspicions = $strategy->apply($message);

        static::assertTrue($message->is_spam_suspicions, 'set the spam suspicion strategy failed');

        $message['subject'] = 'test non spam suspicion subject';
        $message->is_spam_suspicions = $strategy->apply($message);

        static::assertFalse($message->is_spam_suspicions, 'set the spam non-suspicion strategy failed');
    }

}
