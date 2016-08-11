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

class WidgetMessages extends GenericPage
{
    /**
     * @var MailPage The mail page to which this object belongs
     */
    private $mailPage;

    /**
     * Creates a new WidgetMessages object
     *
     * @param MailPage $mailPage The mail page to which this object belongs
     * @param unknown $rightSection A reference to the right section that
     * contains the widget
     */
    public function __construct(MailPage $mailPage, $rightSection)
    {
        parent::__construct($mailPage->getTestCase(), $rightSection);
        $this->mailPage = $mailPage;
    }

    /**
     * Returns the text of the header displayed on the top of the widget
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->byCssSelector('#subject')->text();
    }

    /**
     * Returns an arrays of MessageUnit objects that are displayed in the
     * current conversation
     *
     * @return array
     */
    public function getArrayOfMessageUnitsCurrentConversation()
    {
        $messageUnits = array();
        foreach ($this->byCssSelectorMultiple('#messagesArea > .Messages_unit') as $messageUnitDiv) {
            $messageUnits[] = new MessageUnit($this->mailPage, $messageUnitDiv);
        }
        return $messageUnits;
    }

    /**
     * Returns the single MessageUnit displayed in the current conversation.
     *
     * @throws \Exception If there is more than 1 message in the conversation
     * @return MessageUnit
     */
    public function getSingleMessageUnitInConversation()
    {
        $messageUnit = $this->getArrayOfMessageUnitsCurrentConversation();
        if (count($messageUnit) > 1) {
            throw new \Exception('There is more than 1 message in the current conversation');
        } else {
            return $messageUnit[0];
        }
    }

    /**
     * Moves the mouse over the subject menu to make it show
     * the available options
     */
    public function moveMouseToSubjectMenu()
    {
        $this->testCase->moveto($this->byCssSelector('#subjectMenu'));
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
     * Opens the Subject menu and click in one the options inside it
     *
     * @param string $itemText The text of the item to be clicked
     *
     * @throws \Exception If there is no option with the specified text
     */
    private function clickOnSubjectMenuItemByText($itemText)
    {
        $this->moveMouseToSubjectMenu();
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("The subject menu item with text $itemText was found");
    }

    /**
     * Opens the Subject menu and clicks in the "Apagar conversa" option
     */
    public function clickSubjectMenuOptionDelete()
    {
        $this->clickOnSubjectMenuItemByText("Apagar conversa");
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Opens the subject menu and clicks in the move thread option
     */
    public function clickSubjectMenuOptionMove($folderName)
    {
        $this->clickOnSubjectMenuItemByText($folderName);
        $this->waitForAjaxAndAnimations();
    }

    /**
     * Opens the subject menu and clicks in "Marcar conversa como lida" option
     */
    public function clickSubjectMenuOptionMarkRead()
    {
        $this->clickOnSubjectMenuItemByText('Marcar conversa como lida');
        // we don't do a waitForAjaxAndAnimations here because
        // this may result in an alert message to be opened

    }

    /**
     * Opens the subject menu and clicks in "Marcar conversa como não lida" option
     */
    public function clickSubjectMenuOptionMarkUnread()
    {
        $this->clickOnSubjectMenuItemByText('Marcar conversa como não lida');
        $this->waitForAjaxAndAnimations();

    }
}
