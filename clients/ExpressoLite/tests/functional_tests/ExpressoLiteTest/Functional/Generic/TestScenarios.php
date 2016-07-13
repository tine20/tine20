<?php

/**
 * Expresso Lite
 * This class has the purpose to automatically mount initial test scenarios.
 * This is useful to avoid repeating the steps in tests that share the same
 * initial situation.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

use ExpressoLiteTest\Functional\Mail\MailPage;

abstract class TestScenarios
{
    /**
     * Creates a test scenario in which 2 users send each other e-mails
     * to create a conversation. It does the following steps:
     * 1 - User 1 logs in and sends an e-mail;
     * 2 - User 2 logs in and replies the e-mail back to user 1;
     * 3 - User 1 logs in again and re-replies the e-mail back to user 2.
     * At the end of this procedures, there will be the following situation:
     * a) System will be at the login screen and
     * b) User 2 will have a 2 message conversation in his Inbox folder.
     *
     * @param ExpressoLiteTest $test The test case that will create the scenario
     * @param stdObj $params A object containing all the necessary parameters for this scenario.
     * This object must have the following fields.
     * "user1" => login for first user,
     * "password1" => password for first user,
     * "user2" => login for second user,
     * "password1" => password for second user,
     * "mail2" => e-mail address for the second user,
     * "subject" => subject of the e-mails of the conversation
     * "content" => content of the e-mails of the conversation
     */
    public static function create2MessageConversation(ExpressoLiteTest $test, $params)
    {
        //testStart - part one

        $test->doLogin($params->user1, $params->password1);
        $mailPage = new MailPage($test);

        $mailPage->sendMail(array($params->mail2), $params->subject, $params->content);
        $mailPage->clickLogout();

        // Second user

        $test->doLogin($params->user2, $params->password2);

        $mailPage->waitForEmailToArrive($params->subject);
        $mailPage->clickOnHeadlineBySubject($params->subject);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionReply();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->typeMessageBodyBeforeSignature('Reply Content: ' . $params->content);
        $widgetCompose->clickSendMailButton();
        $widgetCompose->waitForAjaxAndAnimations();

        $mailPage->clickLayoutBackButton();

        $mailPage->clickLogout();

        // Re-login of sender

        $test->doLogin($params->user1, $params->password1);

        $reSubject = 'Re: ' . $params->subject;
        $mailPage->waitForEmailToArrive($reSubject);
        $mailPage->clickOnHeadlineBySubject($reSubject);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionReply();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->typeMessageBodyBeforeSignature('Reply of Reply Content: ' . $params->content);
        $widgetCompose->clickSendMailButton();
        $widgetCompose->waitForAjaxAndAnimations();

        $mailPage->clickLayoutBackButton();

        $mailPage->clickLogout();
    }

    /**
     * Creates a test scenario to create a message in draft folder
     *
     * @param ExpressoLiteTest $test The test case that will create the scenario
     * @param stdObj $params A object containing all the necessary parameters for this scenario.
     * This object must have the following fields.
     *
     * senderLogin => login of the user that will create the draft
     * senderPassword => password of the user that will create the draft
     * recipentMail => the draft recipent's e-mail address
     * subject => subject of the draft
     * content => content of the draft
     */
    public static function createMessageDraft(ExpressoLiteTest $test, $params)
    {
        $test->doLogin($params->senderLogin, $params->senderPassword);
        $mailPage = new MailPage($test);

        $mailPage->clickWriteEmailButton();
        $widgetCompose = $mailPage->getWidgetCompose();
        $widgetCompose->clickOnRecipientField();

        $widgetCompose->type($params->recipentMail);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($params->subject);
        $widgetCompose->typeMessageBodyBeforeSignature($params->content);
        $widgetCompose->clickSaveDraftButton();
        $widgetCompose->waitForAjaxAndAnimations();

        $mailPage->clickLogout();
    }
}
