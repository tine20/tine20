<?php
/**
 * Expresso Lite
 * A Page Object that represents a single entry in the headlines list area
 * of the mail module
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class HeadlinesEntry extends GenericPage
{
    /**
     * @var READ_STATUS Status string of a message that was already read
     */
    const READ_STATUS = 'read';

    /**
     * @var UNREAD_STATUS Status string of a message that was not yet read
     */
    const UNREAD_STATUS = 'unread';

    /**
     * @var UNKNOWN_STATUS Status string of a message for which we can't determine if it is read or not (usually indicates an error)
     */
    const UNKNOW_STATUS = 'unknow';

    /**
     * @var MailPage The mail page to which this entry belongs
     */
    private $mailPage;

    /**
     * Creates a new HeadlinesEntry object
     *
     * @param MailPage $mailPage The mail page to which this entry belongs
     * @param unknown $headlinesEntryDiv A reference to the main div of this headline
     */
    public function __construct(MailPage $mailPage, $headlinesEntryDiv)
    {
        parent::__construct($mailPage->getTestCase(), $headlinesEntryDiv);
        $this->mailPage = $mailPage;
    }

    /**
     * Returns the subject displyed on this entry
     *
     * @return string The entry subject
     */
    public function getSubject()
    {
        return $this->byCssSelector('.Headlines_subject')->text();
    }

    /**
     * Checks if this HeadlinesEntry displays the important icon
     *
     * @return boolean True if the important icon is displayed in this entry, false otherwise
     */
    public function hasImportantIcon()
    {
        return $this->isElementPresent('.icoImportant');
    }

    /**
     * Checks if this HeadlinesEntry displays the Toggle Highlight
     *
     * @return boolean True if the highliht icon is displayed in this entry, false otherwise
     */
    public function hasHighlightIcon()
    {
        return $this->isElementPresent('.icoHigh1');
    }

    /**
     * Returns the sender name displyed on this entry
     *
     * @return string The sender name
     */
    public function getSender()
    {
        return $this->byCssSelector('.Headlines_sender')->text();
    }

    /**
     * Clicks on the headline checkbox to toggle its selected/unselected status
     */
    public function  toggleCheckbox()
    {
        $this->byCssSelector('.icoCheck0')->click();
    }

    /**
     * Returns a status string indicating if this headline has been read or not
     *
     * @returns string HeadlinesEntry::READ_STATUS if the message
     *                   is already read, HeadlinesEntry::UNREAD_STATUS if the
     *                   message is not yet read OR
     *                   HeadlinesEntry::UNKNOW_STATUS if it's not possible to
     *                   determine if the message is read or not (usually indicates
     *                   an error situation)
     */
    public function getReadStatus()
    {
        if (strpos($this->attribute('class'), 'Headlines_entryUnread') != false) {
            return self::UNREAD_STATUS;
        } else if (strpos($this->attribute('class'), 'Headlines_entryRead') != false) {
            return self::READ_STATUS;
        } else {
            return self::UNKNOW_STATUS;
        }
    }
}
