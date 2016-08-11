<?php
/**
 * Expresso Lite
 * This test case checks if drafts can be successfully created, updated and deleted
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

class UpdateDraftTest extends ExpressoLiteTest
{
    /*
     * Overview:
     * - This test checks if a saved draft can have its fields successfully updated
     *
     * CTV3-844
     *  http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-844
     */
    public function test_CTV3_844_Change_Draft()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_1_MAIL = $this->getGlobalValue('user.2.email');
        $RECIPIENT_2_MAIL = $this->getGlobalValue('user.3.email');
        $BADGE_1 = $this->getGlobalValue('user.2.badge');
        $BADGE_2 = $this->getGlobalValue('user.3.badge');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        TestScenarios::createMessageDraft($this, (object) array(
            'senderLogin' => $USER_LOGIN,
            'senderPassword' => $USER_PASSWORD,
            'recipentMail' => $RECIPIENT_1_MAIL,
            'subject' => $MAIL_SUBJECT,
            'content' => $MAIL_CONTENT
        ));

        $this->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $mailPage->clickOnFolderByName('Rascunhos');
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimations();

        $widgetCompose = $mailPage->getWidgetCompose();
        $widgetCompose->clickOnBadgeByName($BADGE_1);
        $this->waitForAjaxAndAnimations();

        $this->assertCount(0, $widgetCompose->getArrayOfCurrentBadges(),
                'The recipient field is not null');

        $widgetCompose->clickOnRecipientField();

        $widgetCompose->type($RECIPIENT_2_MAIL);
        $widgetCompose->typeEnter();
        $this->assertEquals(array($BADGE_2), $widgetCompose->getArrayOfCurrentBadges(),
                'The displayed badges do not match what was expected');

        $widgetCompose->typeSubject(' CHANGED');
        // The string will NOT erase the current content of the subject field.
        // It will be simply APPENDED at the end of field

        $this->assertEquals($MAIL_SUBJECT . ' CHANGED', $widgetCompose->getSubject(),
                'The displayed subject do not match what was expected');

        $widgetCompose->typeMessageBodyBeforeSignature('CHANGED ');
        $this->assertContains('CHANGED ', $widgetCompose->getMessageBodyText(),
                'The displayed body message do not match what was expected (added content)');
        $this->assertContains($MAIL_CONTENT, $widgetCompose->getMessageBodyText(),
                'The displayed body message do not match what was expected (original content)');

        $widgetCompose->clickSaveDraftButton();
        $this->waitForAjaxAndAnimations();

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT . ' CHANGED');
        $this->waitForAjaxAndAnimations();

        $widgetCompose = $mailPage->getWidgetCompose();

        $this->assertEquals(array($BADGE_2), $widgetCompose->getArrayOfCurrentBadges(),
                'The displayed badges do not match what was expected after open message second time');
        $this->assertEquals($MAIL_SUBJECT . ' CHANGED', $widgetCompose->getSubject(),
                'The displayed subject do not match what was expected after open message second time');
        $this->assertContains('CHANGED ' . $MAIL_CONTENT,$widgetCompose->getMessageBodyText(),
                'The displayed body message do not match what was expected after open message second time');

        $widgetCompose->clickSaveDraftButton();
        $this->waitForAjaxAndAnimations();
    }

    /*
     * Overview:
     * - This test checks if opening and sending a draft works as expected
     *
     * CTV3-845
     *  http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-845
     */
    public function test_CTV3_845_Send_Draft()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_1_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        TestScenarios::createMessageDraft($this, (object) array(
            'senderLogin' => $USER_LOGIN,
            'senderPassword' => $USER_PASSWORD,
            'recipentMail' => $RECIPIENT_1_MAIL,
            'subject' => $MAIL_SUBJECT,
            'content' => $MAIL_CONTENT
        ));

        $this->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $mailPage->clickOnFolderByName('Rascunhos');
        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimations();

        $widgetCompose = $mailPage->getWidgetCompose();
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimations();

        $this->assertFalse($widgetCompose->isDisplayed(),
                'Compose draft window should have been closed, but it is still visible');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull($headlinesEntry,
                "A mail with subject $MAIL_SUBJECT was not sent, but it could be found on Draft folder");

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry,
                "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");
    }

    /*
     * Overview:
     * - This tests checks if the deletion of a draft message
     *
     * CTV3-846
     *  http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-846
     */
    public function test_CTV3_846_Delete_Draft()
    {
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $RECIPIENT_1_MAIL = $this->getGlobalValue('user.2.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        TestScenarios::createMessageDraft($this, (object) array(
            'senderLogin' => $USER_LOGIN,
            'senderPassword' => $USER_PASSWORD,
            'recipentMail' => $RECIPIENT_1_MAIL,
            'subject' => $MAIL_SUBJECT,
            'content' => $MAIL_CONTENT
        ));

        $this->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);

        $mailPage->clickOnFolderByName('Rascunhos');
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $headlinesEntry->toggleCheckbox();

        $mailPage->clickMenuOptionDelete();
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNull(
                $headlinesEntry,
                'Mail was deleted, but it was not removed from headlines listing in Draft Folder');

        $mailPage->clickOnFolderByName('Lixeira');
        $this->waitForAjaxAndAnimations();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull(
                $headlinesEntry,
                'Mail was deleted, but could not be found in the trash bin');
    }
}
