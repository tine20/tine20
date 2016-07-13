<?php
/**
 * Expresso Lite
 * This test case checks if the message was marked as read or unread
 * in Inbox Folder
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class MarkReadUnreadTest extends SingleLoginTest {
    /**
     * Overwrites superclass getTestUrl to indicate that this module should
     * always redirect to the main mail module before each test.
     *
     * @see \ExpressoLiteTest\Functional\Generic\SingleLoginTest::getTestUrl()
     */
    public function getTestUrl()
    {
        return LITE_URL . '/mail';
    }

    /**
     * Overview:
     * This test selects and marks a message in the headlines listing of the
     * Inbox folder, then clicks the "Marcar como lida" in menu option. After that
     * it checks if the message was really marked as read.
     *
     * - CTV3-754
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-754
     *
     */
    public function test_CTV3_754_MarkReadMail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        //testStart
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL_SUBJECT, 'Marcando como lido');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionMarkRead();
        $this->waitForAjaxAndAnimations();
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertEquals(HeadlinesEntry::READ_STATUS,$headlinesEntry->getReadStatus(),
                'The message was marked as "Read", but it was not changed');
    }

    /**
     * Overview:
     * - This opens a message and then clicks the "Marcar como não lida" options
     *   of the message menu. After that, checks if the message was really marked
     *   as unread and then checks if the system shows the correct message if
     *   the user tries mark them as unread for a second time
     *
     * - CTV3-754
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-754
     *
     */
    public function test_CTV3_754_MarkUnreadMail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        //testStart
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL_SUBJECT, 'Marcando como não lido');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $widgetMessages->getSingleMessageUnitInConversation();
        $mailPage->clickLayoutBackButton();
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionMarkUnread();

        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertEquals(HeadlinesEntry::UNREAD_STATUS,$headlinesEntry->getReadStatus(),
                'The message was marked as "Unread", but it was not changed');
        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionMarkUnread();
        $this->assertAlertTextEquals(
                'Nenhuma mensagem a ser marcada como não lida.',
                'System did not show message indicating no message tobe marked as unread');

        $this->acceptAlert();

    }

    /**
     * Overview:
     * This test ,it is open and close the message.Selects and marks a message and
     * clicks the "Marcar como não lida" in menu option. Checks if the message was
     * really marked as unread
     *
     * - CTV3-1057
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1057
     */
    public function test_CTV3_1057_MarkUnreadOpenMail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        //testStart
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL_SUBJECT, 'Marcando como não lido');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $headlinesEntry->click();
        $this->waitForAjaxAndAnimations();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionMove('Marcar como não lida');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertEquals(HeadlinesEntry::UNREAD_STATUS,$headlinesEntry->getReadStatus(),
                'The message was marked as "Unread", but it was not changed');
    }

}
