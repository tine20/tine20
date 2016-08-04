<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite mail module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class MailPage extends GenericPage
{
    /**
     * Clicks on the Write email button on the left of the screen
     * and waits for the compose window to be displayed
     */
    public function clickWriteEmailButton()
    {
        $this->byCssSelector('#btnCompose')->click();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the Refresh button on the left of the screen
     * and waits for the compose window to be displayed
     */
    public function clickRefreshButton()
    {
        $this->byCssSelector('#btnUpdateFolders')->click();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the Addressbook link on the left of the screen
     * and waits for the Addressbook window to be displayed
     */
    public function clickAddressbook()
    {
        $this->byCssSelector('.Layout_iconAddress')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on the Calendar link on the left of the screen
     * and waits for the Calendar window to be displayed
     */
    public function clickCalendar()
    {
        $this->byCssSelector('.Layout_iconCalendar')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Clicks on a folder contained on the folder tree based on its name
     *
     * @param string $folderName The name of the folder to be clicked
     */
    public function clickOnFolderByName($folderName)
    {
        $folderNames = $this->byCssSelectorMultiple('#foldersArea .Folders_folderName');
        foreach ($folderNames as $folderNameDiv) {
            if ($folderNameDiv->text() == $folderName) {
                $folderNameDiv->click();
                break;
            }
        }
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Types the text for search criteria
     */
    public function typeSearchText($text)
    {
        $this->byCssSelector('#Layout_txtSearch')->value( $text);
    }

    /**
     * Clears the content of the text search field
     */
    public function clearSearchField()
    {
        return $this->byCssSelector('#Layout_txtSearch')->clear();
    }

    /**
     * Clicks in the search button
     */
    public function clickSearchButton()
    {
        $this->byCssSelector('.Layout_iconSearch')->click();
    }

    /**
     * Returns a HeadlineEntry that represents the item of the headlines list that.
     * contains a specific subject. If there are no e-mails with the specified subject,
     * it returns nulll.
     *
     * @param string $subject The subject to be searched for
     *
     * @return HeadlinesEntry The headline that contains the specified subject, null if
     * no e-mail with the specified subject was found
     */
    public function getHeadlinesEntryBySubject($subject)
    {
        foreach($this->byCssSelectorMultiple('#headlinesArea > .Headlines_entry') as $headlinesEntryDiv) {
            $entry = new HeadlinesEntry($this, $headlinesEntryDiv);
            if ($entry->getSubject() == $subject) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Returns an array containing all headlines entries being displayed on the screen
     *
     * @return array Array of HeadlineEntry objects
     */
    public function getArrayOfHeadlinesEntries()
    {
        $entries = array();
        foreach($this->byCssSelectorMultiple('#headlinesArea > .Headlines_entry') as $headlinesEntryDiv) {
            $entries[] = new HeadlinesEntry($this, $headlinesEntryDiv);
        }
        return $entries;
    }

    /**
     * Clicks on the headline in the headlines list that contains a specified subject
     *
     * @param string $subject The subject to be searched for
     *
     * @throws \Exception If no headlines entry was found
     */
    public function clickOnHeadlineBySubject($subject)
    {
        $headline = $this->getHeadlinesEntryBySubject($subject);
        if ($headline == null) {
            throw new \Exception('Could not find a headline with subject ' . $subject);
        } else {
            $headline->click();
            $this->waitForAjaxAndAnimations();
        }
    }

    /**
     * Performs all the steps involved in sending a simple e-mail to one or more recipients.
     * This involves the following steps: 1 - click the Write button, 2 - For each recipient,
     * type its name followed by an ENTER key, 3 - Write the subject, 4 - Write the content
     * of the e-mail, 5 - Click the Send button
     *
     * @param array $recipients An array of strings containing the recipients of the e-mail
     * @param string $subject The subject of the e-mail
     * @param string $content The content to be written in the e-mail
     */
    public function sendMail($recipients, $subject, $content)
    {
        $this->clickWriteEmailButton();
        $this->waitForAjaxAndAnimations();
        $widgetCompose = $this->getWidgetCompose();
        $widgetCompose->clickOnRecipientField();

        foreach ($recipients as $recipient) {
            $widgetCompose->type($recipient);
            $widgetCompose->typeEnter();
        }

        $widgetCompose->typeSubject($subject);

        $widgetCompose->typeMessageBodyBeforeSignature($content);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Clicks the Logout button and wait for the login screen to be displayed
     */
    public function clickLogout()
    {
        $this->byCssSelector('#Layout_logoff')->click();
        $this->testCase->waitForUrl(LITE_URL . '/');
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Clicks the Back ( <-- ) button in the top of the screen
     */
    public function clickLayoutBackButton()
    {
        $this->byCssSelector('#Layout_arrowLeft')->click();
    }

    /**
     * Returns a WidgetCompose Page Object that represents the
     * compose window currently being displayed by the mail module
     *
     * @return WidgetCompose
     */
    public function getWidgetCompose()
    {
        return new WidgetCompose(
                $this,
                $this->byCssSelector('body > .Dialog_box')); //'body >' will filter templates out
    }

    /**
     * Returns a ContactsAutoComplete Page Object that represents the
     * contacts list currently being displayed the recipient autocomplete box
     *
     * @return ContactsAutoComplete
     */
    public function getContactsAutoComplete()
    {
        return new ContactsAutoComplete(
                $this,
                $this->byCssSelector('body > .ContactsAutocomplete_frame'));
    }

    /**
     * Returns the WidgetMessages Page Object that represents the message details
     * currently being displayed in the mail module
     *
     * @return WidgetMessages
     */
    public function getWidgetMessages()
    {
        return new WidgetMessages($this, $this->byCssSelector('#rightBody'));
    }

    /**
     * Returns an array of <li> elements within the context menu
     *
     * @returns array Array of <li> elements within the context menu
     */
    private function getContextMenuItems()
    {
        return $this->byCssSelectorMultiple('.ContextMenu_liOption');
    }

    /**
     * Clicks on a menu item within the context menu
     *
     * @param string $itemText The text of the item to be clicked
     */
    private function clickOnMenuItemByText($itemText)
    {
        $this->moveMouseToOptionsMenu();
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("Menu item with text $itemText was not found");
    }

    /**
     * Moves the mouse over the options menu to make it show its contents
     */
    public function moveMouseToOptionsMenu()
    {
        $this->testCase->moveto($this->byCssSelector('#Layout_context'));
    }

    /**
     * Opens the options menu and clicks on the "Apagar" option
     */
    public function clickMenuOptionDelete()
    {
        $this->clickOnMenuItemByText('Apagar');
    }

    /**
     * Opens the options menu and clicks on the "Marcar como lida" option
     */
    public function clickMenuOptionMarkRead()
    {
        $this->clickOnMenuItemByText('Marcar como lida');
    }

    /**
     * Opens the options menu and clicks on the "Marcar como não lida" option
     */
    public function clickMenuOptionMarkUnread()
    {
        $this->clickOnMenuItemByText('Marcar como não lida');
    }

    /**
     * Opens the options menu and clicks on the "Alterar destaque" option
     */
    public function clickMenuOptionHighlight()
    {
        $this->clickOnMenuItemByText('Alterar destaque');
    }

    /**
     * Opens the options menu and clicks on the Folder destination option
     */
    public function clickMenuOptionMove($folderName)
    {
        $this->clickOnMenuItemByText($folderName);
    }

    /**
     * This method checks if an email with subject $subject has arrived. If
     * not, waits for 5 seconds, refreshes the screen and tries again. If the
     * e-mail is not found after 3 attempts, an exception is thrown
     *
     * @param string $subject The subject of the expected e-mail
     */
    public function waitForEmailToArrive($subject)
    {
        for ($i=0; $i < 6; $i++) {
            $headline = $this->getHeadlinesEntryBySubject($subject);
            if ($headline != null) {
                return;
            } else {
                sleep(5); //wait 5s and before trying again
                $this->clickRefreshButton();
                $this->waitForAjaxAndAnimations();
            }
        }

        throw new \Exception("Waited for e-mail with subject $subject but it never arrived");
    }

    /**
     * Checks if the contacts list was displayed after typing two character
     * in the recipient field
     *
     * @return boolean
     */
    public function hasContactLoadMoreButton()
    {
        return $this->isElementPresent('.WidgetContactList_footer > #WidgetContactList_loadMoreButton');
    }

    /**
     * Checks if the Email container was displayed
     *
     * @return boolean
     */
    public function hasEmailScreenListed()
    {
        return $this->isElementPresent('#headlinesArea');
    }
}
