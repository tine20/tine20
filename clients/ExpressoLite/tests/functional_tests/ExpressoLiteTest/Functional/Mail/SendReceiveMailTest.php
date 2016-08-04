<?php
/**
 * Expresso Lite
 * Test case that checks the behavior sending and receiving e-mails.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Login\LoginPage;
use ExpressoLiteTest\Functional\Addressbook\AddressbookPage;

class SendReceiveMailTest extends ExpressoLiteTest
{
    /**
     * Tests sending and receiving a simple e-mail, checking if the
     * data received by the recipient matches the originally sent data
     */
    public function testSendReceiveSimpleMail()
    {
        //load test data
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');
        $SENDER_MAIL = $this->getGlobalValue('user.1.email');
        $SENDER_NAME = $this->getGlobalValue('user.1.name');
        $RECIPIENT_LOGIN = $this->getGlobalValue('user.2.login');
        $RECIPIENT_PASSWORD = $this->getGlobalValue('user.2.password');
        $RECIPIENT_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($RECIPIENT_MAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickLogout();

        $loginPage->doLogin($RECIPIENT_LOGIN, $RECIPIENT_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertEquals($SENDER_NAME, $headlinesEntry->getSender() , 'Headline sender name does not match what was expected');

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $this->assertEquals($MAIL_SUBJECT, $widgetMessages->getHeader(), 'The header in the right body header does not match the expected mail subject: ' . $MAIL_SUBJECT);

        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($SENDER_NAME, $messageUnit->getFromName(), 'Message sender name does not match');
        $this->assertEquals("($SENDER_MAIL)", $messageUnit->getFromMail(), 'Message sender mail does not match');
        $this->assertEquals(array($RECIPIENT_MAIL), $messageUnit->getToAddresses(), 'Message recipient does not match');
        $this->assertContains($MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');
    }

    /**
     * Checks the reply e-mail feature. In this test, user 1 sends an e-mail
     * to user 2, who replies it back to user 1
     *
     * CTV3-757
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-757
     */
    public function test_CTV3_757_SendReceiveReplySimpleMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_1_MAIL = $this->getGlobalValue('user.1.email');
        $USER_1_BADGE = $this->getGlobalValue('user.1.badge');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');
        $REPLY_MAIL_CONTENT = $this->getTestValue('reply.mail.content');

        //testStart
        $loginPage = new LoginPage($this);

        $loginPage->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_2_MAIL), $MAIL_SUBJECT, $ORIGINAL_MAIL_CONTENT);
        $mailPage->clickLogout();

        $loginPage->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionReply();

        $widgetCompose = $mailPage->getWidgetCompose();

        $REPLY_SUBJECT = 'Re: ' . $MAIL_SUBJECT;

        $this->assertEquals(array($USER_1_BADGE), $widgetCompose->getArrayOfCurrentBadges(), 'Reply window did not show the expected recipient');
        $this->assertEquals($REPLY_SUBJECT, $widgetCompose->getSubject() , 'Reply window did not the expected subject');
        $this->assertContains($ORIGINAL_MAIL_CONTENT, $widgetCompose->getMessageBodyText());

        $widgetCompose->typeMessageBodyBeforeSignature($REPLY_MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimations();
        $mailPage->clickLayoutBackButton();
        $this->waitForAjaxAndAnimations();

        $mailPage->clickLogout();
        $loginPage->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);

        $mailPage->waitForEmailToArrive($REPLY_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($REPLY_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertContains($REPLY_MAIL_CONTENT, $messageUnit->getContent(), 'The replied message content was not found in the reply body');
        $this->assertTrue($messageUnit->hasShowQuoteButton(), 'The replied message did not show the Show Quote button');

        $messageUnit->clickShowQuoteButton();

        $this->assertContains($ORIGINAL_MAIL_CONTENT, $messageUnit->getQuoteText(), 'The original message content was not found in the mail quote section');
    }

    /**
     * Checks the forward e-mail feature. In this test, user 1 sends an e-mail
     * to user 2, who forward to user 3
     *
     * CTV3-758
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-758
     */
    public function test_CTV3_758_SendReceiveFowardOpenMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $USER_3_LOGIN = $this->getGlobalValue('user.3.login');
        $USER_3_PASSWORD = $this->getGlobalValue('user.3.password');
        $USER_3_MAIL = $this->getGlobalValue('user.3.email');
        $USER_3_BADGE = $this->getGlobalValue('user.3.badge');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');
        $FORWARD_MAIL_CONTENT = $this->getTestValue('forward.mail.content');

        //testStart
        $loginPage = new LoginPage($this);

        $loginPage->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_2_MAIL), $MAIL_SUBJECT, $ORIGINAL_MAIL_CONTENT);
        $mailPage->clickLogout();

        $loginPage->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionForward();

        $widgetCompose = $mailPage->getWidgetCompose();
        $FORWARD_SUBJECT = 'Fwd: ' . $MAIL_SUBJECT;
        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($USER_3_MAIL);
        $widgetCompose->typeEnter();
        $widgetCompose->typeMessageBodyBeforeSignature($FORWARD_MAIL_CONTENT);

        $this->assertEquals(array($USER_3_BADGE), $widgetCompose->getArrayOfCurrentBadges(), 'Forward window did not show the expected recipient');
        $this->assertEquals($FORWARD_SUBJECT, $widgetCompose->getSubject() , 'Forward window did not the expected subject');
        $this->assertContains($ORIGINAL_MAIL_CONTENT, $widgetCompose->getMessageBodyText());

        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimations();
        $mailPage->clickLayoutBackButton();
        $this->waitForAjaxAndAnimations();

        $mailPage->clickLogout();
        $loginPage->doLogin($USER_3_LOGIN, $USER_3_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->waitForEmailToArrive($FORWARD_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($FORWARD_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertContains($FORWARD_MAIL_CONTENT, $messageUnit->getContent(), 'The forwarded message content was not found in the forward body');
        $this->assertTrue($messageUnit->hasShowQuoteButton(), 'The forwarded message did not show the Show Quote button');

        $messageUnit->clickShowQuoteButton();

        $this->assertContains($ORIGINAL_MAIL_CONTENT, $messageUnit->getQuoteText(), 'The original message content was not found in the mail quote section');
    }

    /**
     * Checks the reply e-mail feature. In this test, user 1 sends an e-mail
     * to new user and this recipient is saved in the personal catalog
     *
     * CTV3-1015
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1015
     */
    public function test_CTV3_1015_Send_Mail_Saved_Recipient()
    {
        //load test data
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_MAIL = $this->getTestValue('recipient.mail');
        $RECIPIENT_NAME = $this->getTestValue('recipient.name');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);

        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($RECIPIENT_MAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();
        $contactListItem = $addressbookPage->getCatalogEntryByName($RECIPIENT_NAME);

        $this->assertEquals($RECIPIENT_NAME, $contactListItem->getNameFromContact(),
                'Mail was send but, the new recipient is not created in personal catalog ');
    }
}
