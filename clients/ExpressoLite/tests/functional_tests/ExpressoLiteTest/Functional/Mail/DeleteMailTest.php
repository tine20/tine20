<?php
/**
 * Expresso Lite
 * Test case that checks e-mail deletion. These tests the focus
 * on the message removal in the trash folder
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

class DeleteMailTest extends ExpressoLiteTest
{
    /**
     *Overview:
     *
     * This test checks the deletion of an e-mail in the headlines listing. It
     * selects an e-mail by clicking its checkbox and then clicks on the "Delete"
     * option within the options menu
    *
     * - CTV3-750
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-750
    */
    public function test_CTV3_750_Delete_Mail()
    {
        //load test data
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_MAIL = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        $this->doLogin($USER_LOGIN, $USER_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_MAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        //testStart

        $this->waitForAjaxAndAnimations();

        $mailPage->clickOnFolderByName('Enviados');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionDelete();
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull(
                $headlinesEntry,
                'Mail was deleted, but it was not removed from headlines listing');

        $mailPage->clickOnFolderByName('Lixeira');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'Mail was deleted, but could not be found in the trash bin');
    }

    /**
     * Overview:
     *
     * - Deletes an opened email message. Sends an e-mail to youself, then checks
     *   if the message is in the Sent folder and then deletes it with option
     *   "Apagar" of the message menu. After that, it checks if the message was
     *   sent to the thash box.
     *
     * - CTV3-751
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-751
     */
    public function test_CTV3_751_Delete_Open_Mail()
    {
        //load test data
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_MAIL = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        $this->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_MAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        //testStart
        $this->waitForAjaxAndAnimations();
        $mailPage->clickRefreshButton();

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->waitForAjaxAndAnimations();

        $messageUnit->clickMenuOptionDelete();

        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull(
               $headlinesEntry,
               'Mail was deleted, but it was not removed from headlines listing');

        $mailPage->clickOnFolderByName('Lixeira');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'Mail was deleted, but could not be found in the trash bin');
    }

    /**
     * Overview:
     *
     * - This test checks the deletion of a conversation containing several
     *   e-mails. After creating a conversations, it selects the
     *   "Apagar conversa" option in the subjects menu and then checks if the
     *   trash folder contains the removed message
     *
     * - CTV3-1017
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1017
     */
    public function test_CTV3_1017_Delete_Open_Thread_Mail()
    {
        $USER_1_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_1_PASSWORD = $this->getGlobalValue('user.1.password');
        $USER_2_LOGIN = $this->getGlobalValue('user.2.login');
        $USER_2_PASSWORD = $this->getGlobalValue('user.2.password');
        $USER_2_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        TestScenarios::create2MessageConversation($this, (object) array(
           'user1' => $USER_1_LOGIN,
           'password1' => $USER_1_PASSWORD,
           'user2' => $USER_2_LOGIN,
           'password2' => $USER_2_PASSWORD,
           'mail2' => $USER_2_MAIL,
           'subject' => $MAIL_SUBJECT,
           'content' => $MAIL_CONTENT
        ));
        $this->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();

        $widgetMessages->clickSubjectMenuOptionDelete();
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull(
               $headlinesEntry,
               'Mail was deleted, but it was not removed from headlines listing');

        $mailPage->clickOnFolderByName('Lixeira');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'Mail was deleted, but could not be found in the trash bin');
    }
}
