<?php
/*
 * Expresso Lite
 * Test case that checks if messages are being moved between folders correctly
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class MoveMailSingleLoginTest extends SingleLoginTest
{
    /**
     * Overwrites superclass getTestUrl to indicate that this module should
     * always redirect to the main mail module before each test
     *
     * @see \ExpressoLiteTest\Functional\Generic\SingleLoginTest::getTestUrl()
     */
    public function getTestUrl()
    {
        return LITE_URL . '/mail';
    }

    /**
     * Overview:
     *
     * - Selects and marks a message in the Inbox folder using option "Mover para
     *   Modelos" of the option menu to move the message to that folder. Checks if
     *   the message is no longer in Inbox and is now in Modelos folder.
     *
     * - CTV3-755
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-755
     *
     */
    public function test_CTV3_755_Move_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

       //testStart
        $mailPage->sendMail(array($MAIL_RECIPIENT), $MAIL_SUBJECT, 'Move email to Modelos folder');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();

        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionMove('Modelos');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull($headlinesEntry,
                'A mail with subject $MAIL_SUBJECT was moved, it could still be found in the inbox folder');

        $mailPage->clickOnFolderByName('Modelos');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'A mail with subject $MAIL_SUBJECT was moved, but it could not be found on Modelos folder');
    }

    /**
     * Overview:
     *
     * - This test opens the details of a message and uses option "Mover para...
     *   Modelos" of the message menu to move the message to that folder. After
     *   that, checks if the message is no longer in Inbox and is now in Modelos folder.
     *
     * - CTV3-1019
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1019
     *
     */
    public function test_CTV3_1019_Move_Open_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

       //testStart
        $mailPage->sendMail(array($MAIL_RECIPIENT), $MAIL_SUBJECT, 'Move open email to Modelos folder');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $headlinesEntry->click();
        $this->waitForAjaxAndAnimations();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $messageUnit->clickMenuOptionMove('Modelos');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertNull($headlinesEntry,
                'A mail with subject $MAIL_SUBJECT was moved, it could still be found in the inbox folder');

        $mailPage->clickOnFolderByName('Modelos');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'A mail with subject $MAIL_SUBJECT was moved, but it could not be found on Modelos folder');
    }
}
