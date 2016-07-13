<?php
/**
 * Expresso Lite
 * Test case that checks the Save Draft feature.
 * we focus save the message in Draft Folder
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class SaveDraftTest extends SingleLoginTest
{
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
      * Overwrites superclass getUserNumber.
      *
      * @see \ExpressoLiteTest\Functional\Generic\SingleLoginTest::getUserNumber()
      */
    public function getUserNumber()
    {
        return 2;
    }

    /**
     * In this test, the e-mail was created and saved in draft folder. Checks if
     * every field matches what was originally typed.
     *
     * CTV3-840
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-840
     */
    public function test_CTV3_840_Save_Draft_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getGlobalValue('user.1.email');
        $MAIL_BADGE = $this->getGlobalValue('user.1.badge');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($MAIL_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);

        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickSaveDraftButton();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickOnFolderByName('Rascunhos');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was saved, but it could not be found on Rascunhos folder");

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimations();

        $widgetCompose = $mailPage->getWidgetCompose();
        $badges = $widgetCompose->getArrayOfCurrentBadges();

        $this->assertEquals($MAIL_SUBJECT, $widgetCompose->getSubject(), 'The subject in windows compose does not match the original mail subject: ' . $MAIL_SUBJECT);
        $this->assertEquals(array($MAIL_BADGE), $badges, 'The mail name in the Compose Window does not match the original mail name:' . $badges[0]);
        $this->assertContains($MAIL_CONTENT, $widgetCompose->getMessageBodyText(),
                            'The Message Body in the Compose Window does not match the original mail message:' . $MAIL_CONTENT);

    }

    /**
     * In this test, the e-mail was created and saved in draft folder. Checks if
     * turn on the important flag.
     *
     * CTV3-843
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-843
     */
    public function test_CTV3_843_Save_Draft_Important_Flag()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($MAIL_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);

        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickImportantRadio();

        $widgetCompose->clickSaveDraftButton();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickOnFolderByName('Rascunhos');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertTrue($headlinesEntry->hasImportantIcon(), 'Headline should have been listed as important, but it was not');

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimations();

        $widgetCompose = $mailPage->getWidgetCompose();
        $this->assertTrue($widgetCompose->isImportantCheckboxChecked(), 'Checkbox was marked as important, but it was not');
    }
}
