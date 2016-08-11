<?php
/**
 * Expresso Lite
 * This test case checks the message search feature
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class SearchTextMessageTest extends SingleLoginTest {
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
     * - This teste checks if the message has the search criteria in the folder.
     *
     * - CTV3-893
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-893
     */
    public function test_CTV3_893_Find_Search_Text()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPENT = $this->getGlobalValue('user.1.email');
        $MAIL1_SUBJECT = $this->getTestValue('mail1.subject');
        $MAIL1_CONTENT = $this->getTestValue('mail1.content');
        $MAIL2_SUBJECT = $this->getTestValue('mail2.subject');
        $MAIL2_CONTENT = $this->getTestValue('mail2.content');
        $MAIL3_SUBJECT = $this->getTestValue('mail3.subject');
        $MAIL3_CONTENT = $this->getTestValue('mail3.content');
        $COMMON_TEXT_FRAGMENT = $this->getTestValue('common.text.fragment');
        $INEXISTENT_TEXT_FRAGMENT = $this->getTestValue('inexistent.text.fragment');

        //testStart
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL1_SUBJECT, $MAIL1_CONTENT);
        $this->waitForAjaxAndAnimations();
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL2_SUBJECT, $MAIL2_CONTENT);
        $this->waitForAjaxAndAnimations();
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL2_SUBJECT, $MAIL2_CONTENT);
        $this->waitForAjaxAndAnimations();
        $mailPage->sendMail(array($MAIL_RECIPENT), $MAIL3_SUBJECT, $MAIL3_CONTENT);
        $this->waitForAjaxAndAnimations();

        $mailPage->waitForEmailToArrive($MAIL1_SUBJECT);
        $mailPage->waitForEmailToArrive($MAIL2_SUBJECT);
        $mailPage->waitForEmailToArrive($MAIL3_SUBJECT);

        $mailPage->typeSearchText($COMMON_TEXT_FRAGMENT);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $messages = $mailPage->getArrayOfHeadlinesEntries();
        $this->assertEquals(3, count($messages),
                'There are diferent number of messages after find criteria applied');

        $mailPage->clearSearchField();
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $mailPage->typeSearchText($INEXISTENT_TEXT_FRAGMENT);
        $mailPage->clickSearchButton();
        $this->waitForAjaxAndAnimations();

        $messages = $mailPage->getArrayOfHeadlinesEntries();
        $this->assertEquals(0, count($messages),
                'The total of messages differs from zero after find criteria applied');
    }
}
