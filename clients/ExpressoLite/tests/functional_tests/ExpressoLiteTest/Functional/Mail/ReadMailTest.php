<?php

/**
 * Expresso Lite
 * Test case that checks the behavior read e-mails
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Generic\TestScenarios;

class ReadMailTest extends ExpressoLiteTest
{
    /**
     * Overview:
     * - In this test, a user sends an e-mail to a recipient. Then, the test
     *   checks if the message details are being displayed correctly in the
     *   recipient account.
     *
     * - CTV3-752
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-752
     */
    public function test_CTV3_752_ReadSimpleMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_1_MAIL = $this->getGlobalValue('user.1.email');
        $USER_1_NAME = $this->getGlobalValue('user.1.name');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');

        //testStart - part one
        $this->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_2_MAIL), $MAIL_SUBJECT, $ORIGINAL_MAIL_CONTENT);
        $mailPage->clickLogout();

        // Second user
        $this->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($USER_1_NAME, $messageUnit->getFromName(), 'Message sender name does not match');
        $this->assertEquals("($USER_1_MAIL)", $messageUnit->getFromMail(), 'Message sender mail does not match');
        $this->assertEquals(array($USER_2_MAIL), $messageUnit->getToAddresses(), 'Message recipent does not match');
        $this->assertContains($ORIGINAL_MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');

        /*
         *  Regular expression for format: weekday, dd/mm/yyyy, hh:MM
         */
        $this->assertRegExp('((domingo|segunda|terça|quarta|quinta|sexta|sábado), \\d\\d/\\d\\d/\\d\\d\\d\\d, \\d\\d:\\d\\d)', $messageUnit->getWhen(), 'header does not match');
    }

    /**
     * Description:
     * - This test checks the reading of an e-mail with a citation in its body.
     *   It performs the following verifications: 1) checks if the message content
     *   matches the original reply from the other user; 2) Checks if there is a
     *   "Show Quote" button; 3) Checks if the citation content matches the content
     *   of the original e-mail
     *
     * - CTV3-1047
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1047
     */
    public function test_CTV3_1047_ReadCitationMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');
        $REPLY_MAIL_CONTENT = $this->getTestValue('reply.mail.content');

        //testStart - part one
        $this->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_2_MAIL), $MAIL_SUBJECT, $ORIGINAL_MAIL_CONTENT);
        $mailPage->clickLogout();

        // Second user
        $this->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionReply();
        $widgetCompose = $mailPage->getWidgetCompose();

        $REPLY_SUBJECT = 'Re: ' . $MAIL_SUBJECT;
        $widgetCompose->typeMessageBodyBeforeSignature($REPLY_MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimations();

        $mailPage->clickLayoutBackButton();
        $mailPage->clickLogout();

        $this->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage->waitForEmailToArrive($REPLY_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($REPLY_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $this->waitForAjaxAndAnimations();

        $this->assertContains($REPLY_MAIL_CONTENT, $messageUnit->getContent(), 'The replied message content was not found in the reply body');
        $this->assertTrue($messageUnit->hasShowQuoteButton(), 'The replied message did not show the Show Quote button');

        $messageUnit->clickShowQuoteButton();

        $this->assertContains($ORIGINAL_MAIL_CONTENT, $messageUnit->getQuoteText(), 'The original message content was not found in the mail quote section');
    }

    /**
     * Description:
     *
     * - This test checks the messages within a conversation are being displayed
     *   correctly. It checks if the conversation has the right number of messages
     *   and if the messages contents are correct.
     *
     * - CTV3-1048
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1048
     *
     */
    public function test_CTV3_1048_ReadThreadMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');
        $REPLY_SECOND_MAIL_CONTENT = $this->getTestValue('reply.second.mail.content');

        TestScenarios::create2MessageConversation($this, (object) array(
           'user1' => $USER_1_LOGIN,
           'password1' => $USER_1_PASSWORD,
           'user2' => $USER_2_LOGIN,
           'password2' => $USER_2_PASSWORD,
           'mail2' => $USER_2_MAIL,
           'subject' => $MAIL_SUBJECT,
           'content' => $ORIGINAL_MAIL_CONTENT
        ));

        $this->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messages = $widgetMessages->getArrayOfMessageUnitsCurrentConversation();
        $this->assertEquals(2, count($messages), 'The number of messages within the conversation does not match what was expected');
        $firstMessage = $messages[0];

        $this->assertFalse($firstMessage->isMessageExpanded(), 'The first message within the conversation was opened, but it shoud have been closed');

        $firstMessage->clickMessageTop();

        $this->assertTrue($firstMessage->isMessageExpanded(), 'The first message in the conversation should have been opened after it was clicked, but it is still closed');
        $this->assertContains($ORIGINAL_MAIL_CONTENT, $firstMessage->getContent(), 'The first message content differs from the expected');

        $secondMessage = $messages[1];

        $this->assertTrue($secondMessage->isMessageExpanded(), 'The second message within the conversation should be opened by default, but it was closed');
        $this->assertContains($REPLY_SECOND_MAIL_CONTENT, $secondMessage->getContent(), 'The second message content differs from the expected');
    }
}
