<?php
/**
 * Expresso Lite
 * This test case checks if the highlight message feature
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class ToggleHighlightTest extends SingleLoginTest {
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
     *
     * - This teste checks if the message changes its highlight status.
     *
     * - CTV3-892
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-892
     *
     */
    public function test_CTV3_892_ToggleHighlightMail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPENT = $this->getGlobalValue('user.1.email');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

       //testStart
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL_SUBJECT, 'Changes its highlight');
        $this->waitForAjaxAndAnimations();

        $mailPage->clickRefreshButton();
        $mailPage->waitForEmailToArrive($MAIL_SUBJECT);
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $headlinesEntry->toggleCheckbox();
        $mailPage->clickMenuOptionHighlight();
        $this->waitForAjaxAndAnimations();
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertTrue(
                $headlinesEntry->hasHighlightIcon(),
                'Headline should have been listed as highlight, but it was not (BEFORE a refresh)'
                );
        $mailPage->clickRefreshButton();
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertTrue(
                $headlinesEntry->hasHighlightIcon(),
                'Headline should have been listed as highlight, but it was not (AFTER a refresh)'
                );

        $headlinesEntry->toggleCheckbox();
        $mailPage->clickMenuOptionHighlight();
        $this->waitForAjaxAndAnimations();
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertFalse(
                $headlinesEntry->hasHighlightIcon(),
                'Headline should not have been listed as highlight, but it was (BEFORE a refresh)'
                );
        $mailPage->clickRefreshButton();
        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertFalse(
                $headlinesEntry->hasHighlightIcon(),
                'Headline should not have been listed as highlight, but it was(AFTER a refresh)'
                );
    }
 }
