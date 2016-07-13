<?php
/**
 * Expresso Lite
 * A Page Object that represents a single message unit displayed
 * in the current conversation of a WidgetMessages
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class MessageUnit extends GenericPage
{
    /**
     * @var MailPage The mail page to which this object belongs
     */
    private $mailPage;

    /**
     * Creates a new MessageUnit object
     *
     * @param MailPage $mailPage The mail page to which this object belongs
     * @param unknown $div The main div that contains the message unit
     */
    public function __construct(MailPage $mailPage, $div)
    {
        parent::__construct($mailPage->getTestCase(), $div);
        $this->mailPage = $mailPage;
    }

    /**
     * Returns the text of the message content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->byCssSelector('.Messages_body')->text();
    }

    /**
     * Returns an array of strings with all addresses in the To field
     *
     *  @return array
     */
    public function getToAddresses()
    {
        return explode(', ', $this->byCssSelector('.Messages_addrTo')->text());
    }

    /**
     * Returns an array of strings with all addresses in the Cc field
     *
     *  @return array
     */
    public function getCcAddresses()
    {
        return explode(', ', $this->byCssSelector('.Messages_addrCc')->text());
    }

    /**
     * Returns an array of strings with all addresses in the Bcc field
     *
     *  @return array
     */
    public function getBccAddresses()
    {
        return explode(', ', $this->byCssSelector('.Messages_addrBcc')->text());
    }

    /**
     * Checks if the Bcc field is displayed in the message
     */
    public function isBccAddressesDisplayed()
    {
        return count($this->byCssSelectorMultiple('.Messages_addrBcc')) > 0;
    }

    /**
     * Checks if the Important icon is displayed in this message unit
     *
     * @return boolean
     */
    public function hasImportantIcon()
    {
        return $this->isElementPresent('.icoImportant');
    }

    /**
     * Returns the name of the sender contained in the From field
     *
     * @return string
     */
    public function getFromName()
    {
        return $this->byCssSelector('.Messages_fromName')->text();
    }

    /**
     * Returns the e-mail of the sender contained in the From field
     *
     * @return string
     */
    public function getFromMail()
    {
        return $this->byCssSelector('.Messages_fromMail')->text();
    }

    /**
     * Return the date of e-mail from header of message
     *
     * @return string
     */
    public function getWhen()
    {
        return $this->byCssSelector('.Messages_when')->text();
    }

    /**
     * Check if has image of sender in header message
     *
     * @return string
     */
    public function hasMugshot()
    {
        return $this->byCssSelector('.Messages_mugshot')->isElementPresent('img');
    }

    /**
     * Moves the mouse over the message unit dropdown menu to make it show
     * the available options
     */
    public function moveMouseToDropdownMenu()
    {
        $this->testCase->moveto($this->byCssSelector('.Messages_dropdown'));
    }

    /**
     * Returns an array of DOM elements that represent each option available
     * in the currently visible context menu
     *
     *  @return array
     */
    private function getContextMenuItems()
    {
        return $this->mailPage->byCssSelectorMultiple('.ContextMenu_liOption');
    }

    /**
     * Checks f and specified menu item exists within the context menu
     *
     * @param string $itemText The text of the item to be searched
     *
     * @returns boolean True if there is an item with the specified text, false otherwise
     */
    public function hasContextMenuItem($itemText)
    {
        $this->moveMouseToDropdownMenu();
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                return true;
            }
        }
        return false;
    }

    /**
     * Opens the dropdown context menu and click in one the options inside it
     *
     * @param string $itemText The text of the item to be clicked
     *
     * @throws \Exception If there is no option with the specified text
     */
    private function clickOnMenuItemByText($itemText)
    {
        $this->moveMouseToDropdownMenu();
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("No menu item with text $itemText was found");
    }

    /**
     * Opens the dropdown context menu and clicks in the "Responder" option
     */
    public function clickMenuOptionReply()
    {
        $this->clickOnMenuItemByText("Responder");
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Opens the dropdown context menu and clicks in the "Encaminhar" option
     */
    public function clickMenuOptionForward()
    {
        $this->clickOnMenuItemByText("Encaminhar");
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Opens the dropdown context menu and clicks in the "Apagar" option
     */
    public function clickMenuOptionDelete()
    {
        $this->clickOnMenuItemByText("Apagar");
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Opens the dropdown context menu and clicks in the folder destination option
     */
    public function clickMenuOptionMove($folderName)
    {
        $this->clickOnMenuItemByText($folderName);
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Checks if the message unit displays a Show Quote button
     *
     * @return boolean
     */
    public function hasShowQuoteButton()
    {
        return $this->isElementPresent('.Messages_showQuote');
    }

    /**
     * Clicks on the Show Quote button contained in the message unit
     */
    public function clickShowQuoteButton()
    {
        $this->byCssSelector('.Messages_showQuote')->click();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Returns the text of the quoted message
     *
     * @return string
     */
    public function getQuoteText()
    {
        return $this->byCssSelector('.Messages_quote')->text();
    }

    /**
     * Clicks on the message top to expand / retract the message details
     */
    public function clickMessageTop()
    {
        $this->byCssSelector('.Messages_top1')->click();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Check if was message thread expanded or not
     */
    public function isMessageExpanded()
    {
        return $this->byCssSelector('.Messages_top2')->displayed();
    }
}
