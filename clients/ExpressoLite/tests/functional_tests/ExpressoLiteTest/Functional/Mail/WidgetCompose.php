<?php
/**
 * Expresso Lite
 * A Page Object that represents the e-mail compose window
 * of the mail module
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;
use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;

class WidgetCompose extends GenericPage
{
    /**
     * @var MailPage The mail page to which this object belongs
     */
    private $mailPage;

    /**
     * Creates a new WidgetCompose object
     *
     * @param MailPage $mailPage The mail page to which this object belongs
     * @param unknown $dialogBoxSection A reference to the main section
     * of the compose window
     */
    public function __construct(MailPage $mailPage, $dialogBoxSection)
    {
        parent::__construct($mailPage->getTestCase(), $dialogBoxSection);
        $this->mailPage = $mailPage;
    }

    /**
     * Clicks on the recipient field
     */
    public function clickOnRecipientField()
    {
        $this->byCssSelector('.Compose_to')->click();
    }

    /**
     * Clears the content of the recipient field
     */
    public function clearRecipientField()
    {
        return $this->byCssSelector('.Compose_to .TextBadges_inputLi input')->clear();
    }

    /**
     * Clicks on a badge based on its name
     *
     *  @param string $name The name displayed on the badge to be clicked
     */
    public function clickOnBadgeByName($name)
    {
        foreach ($this->byCssSelectorMultiple('ul.TextBadges_ul > li.TextBadges_badgeLi') as $badgeLi) {
            if ($badgeLi->text() == $name) {
                $badgeLi->click();
                $this->waitForAjaxAndAnimations();
                return;
            }
        }
        throw new \Exception("No badge with name $name found");
    }

    /**
     * Clicks on cc toggle button
     */
    public function clickOnCcToggleButton()
    {
        $this->byCssSelector('.Compose_ccToggle')->click();
    }

    /**
     * Clicks on bcc toggle button
     */
    public function clickOnBccToggleButton()
    {
        $this->byCssSelector('.Compose_bccToggle')->click();
    }

    /**
     * Types a value in the Subject field
     *
     * @param string $subject The subject to be typed into the field
     */
    public function typeSubject($subject)
    {
        $this->byCssSelector('.Compose_subject')->value( $subject);
    }

    /**
     * Types the body of the message
     *
     * @param string $messageBody
     */
    public function typeMessageBodyBeforeSignature($messageBody)
    {
        $this->testCase->execute(array(
                'script' => "$('.Compose_body:visible').focus().prepend(document.createTextNode('$messageBody'));",
                'args' => array()
        ));
    }

    /**
     * Clicks on the Send button
     */
    public function clickSendMailButton()
    {
        $this->byCssSelector('.Compose_send')->click();
        // we don't do a waitForAjaxAndAnimations here because
        // this may result in an alert message to be opened
    }

    /*
     * Clicks on save to draft button
     */
    public function clickSaveDraftButton()
    {
        $this->byCssSelector('.Compose_draft')->click();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the Important radio box
     */
    public function clickImportantRadio()
    {
        $this->byCssSelector('.Compose_important')->click();
    }

    /**
     * Checks if the compose window is currently being displayed
     *
     * @return boolean Returns true if the compose window is visible, false otherwise
     */
    public function isDisplayed()
    {
        return $this->testCase->isElementDisplayed($this->rootContext);
    }

    /**
     * Returns the names displayed on each badge
     *
     * @return array An array of strings containing the names displayed on each badge
     */
    public function getArrayOfCurrentBadges()
    {
        $badges = array();
        foreach ($this->byCssSelectorMultiple('ul.TextBadges_ul > li.TextBadges_badgeLi') as $badgeLi) {
            $badges[] = $badgeLi->text();
        }
        return $badges;
    }

    /**
     * Returns the value of the currently visible recipient name
     *
     * @return string
     */
    public function getRecipientFieldValue()
    {
        return $this->byCssSelector('.Compose_to .TextBadges_inputLi input')->attribute('value');
    }

    /**
     * Returns the value of the subject field
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->byCssSelector('.Compose_subject')->attribute('value');
    }

    /**
     * Returns the body of the current e-mail
     *
     * @return string
     */
    public function getMessageBodyText()
    {
        return $this->byCssSelector('.Compose_body')->text();
    }

    /**
     * Checks if the compose_important is currently being selected
     * @return boolean Returns true if the compose_important is marked, false otherwise
     */
    public function isImportantCheckboxChecked()
    {
        return $this->byCssSelector('.Compose_important')->selected();
    }
}
