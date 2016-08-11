<?php
/**
 * Expresso Lite
 *  Test case that checks if modifications in the read/unread status of
 *  messages are being done correctly. This tests focus on conversations,
 *  not single messages.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Generic\TestScenarios;

class MarkReadUnreadThreadTest extends ExpressoLiteTest
{
    /**
    * Overview:
    *
    *  This test opens a conversation, selects the option "Marcar conversa como
    *  não lida" in conversation menu and then checks if the message was really
    *  marked as unread.
    *
    * - CTV3-1018
    *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1018
    * Dados de entrada:
    *
    */
    public function test_CTV3_1018_MarkUnreadThreadMail()
    {
        //load test data
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_LOGIN = $this->getGlobalValue('user.2.login');
        $RECIPIENT_PASSWORD = $this->getGlobalValue('user.2.password');	
        $RECIPIENT_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');

        TestScenarios::create2MessageConversation($this, (object) array(
           'user1' => $SENDER_LOGIN,
           'password1' => $SENDER_PASSWORD,
           'user2' => $RECIPIENT_LOGIN,
           'password2' => $RECIPIENT_PASSWORD,
           'mail2' => $RECIPIENT_MAIL,
           'subject' => $MAIL_SUBJECT,
           'content' => $ORIGINAL_MAIL_CONTENT
        ));

        $this->doLogin($RECIPIENT_LOGIN, $RECIPIENT_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimations();

        $widgetMessages = $mailPage->getWidgetMessages();

        $widgetMessages->clickSubjectMenuOptionMarkUnread();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertEquals(HeadlinesEntry::UNREAD_STATUS,$headlinesEntry->getReadStatus(),
                'The message was marked as "Unread", but it was not changed');
    }

    /**
     * Overview:
     *
     * This test opens a conversation, selects option "Marcar conversa como lida"
     * in the conversation menu, and then checks if the message was really marked
     * as read.
     *
     * - CTV3-1058
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1058
     */
    public function test_CTV3_1058_MarkReadThreadMail()
    {
        //load test data
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_LOGIN = $this->getGlobalValue('user.2.login');
        $RECIPIENT_PASSWORD = $this->getGlobalValue('user.2.password');
        $RECIPIENT_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');

        TestScenarios::create2MessageConversation($this, (object) array(
           'user1' => $SENDER_LOGIN,
           'password1' => $SENDER_PASSWORD,
           'user2' => $RECIPIENT_LOGIN,
           'password2' => $RECIPIENT_PASSWORD,
           'mail2' => $RECIPIENT_MAIL,
           'subject' => $MAIL_SUBJECT,
           'content' => $ORIGINAL_MAIL_CONTENT
        ));

        $this->doLogin($RECIPIENT_LOGIN, $RECIPIENT_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimations();

        $widgetMessages = $mailPage->getWidgetMessages();
        $widgetMessages->clickSubjectMenuOptionMarkUnread();

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimations();
        $widgetMessages = $mailPage->getWidgetMessages();
        $widgetMessages->clickSubjectMenuOptionMarkRead();
        $this->waitForAjaxAndAnimations();

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertEquals(HeadlinesEntry::READ_STATUS,$headlinesEntry->getReadStatus(),
                'The message was marked as "Read", but it was not changed');

        $widgetMessages->clickSubjectMenuOptionMarkRead();

        $this->assertAlertTextEquals(
                'Nenhuma mensagem a ser marcada como  lida.',
                'System did not show message indicating no message tobe marked as read');
        $this->dismissAlert();
    }
}
