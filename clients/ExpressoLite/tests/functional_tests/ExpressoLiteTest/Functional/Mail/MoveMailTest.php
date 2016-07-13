<?php
/**
 * Expresso Lite
 * Test case that checks if a conversation with several messages is correctly 
 * moved to another folder
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


class MoveMailTest extends ExpressoLiteTest
{
    /**
     * Overview:
     *
     * This tests moves an opened conversation to folder "Modelo", and then
     * checks if the message is no longer present in the Inbox folder, but is
     * present in the target folder.
     *
     * - CTV3-1020
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1020
     *
     */

    public function test_CTV3_1020_Move_Thread_Mail()
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
        $widgetMessages->clickSubjectMenuOptionMove("Modelos");
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull(
               $headlinesEntry,
               'Mail was moved, but it was not removed from headlines listing');

        $mailPage->clickOnFolderByName('Modelos');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'Mail was moved, but could not be found in the Modelos folder');

        $messages = $widgetMessages->getArrayOfMessageUnitsCurrentConversation();
        $this->assertEquals(2, count($messages),
                'Conversation has less messages after it was moved to another folder');
    }
}
