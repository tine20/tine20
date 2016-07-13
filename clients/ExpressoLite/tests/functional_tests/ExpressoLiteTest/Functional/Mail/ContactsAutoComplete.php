<?php
/**
 * Expresso Lite
 * A page object that represents Expresso Lite ContactsAutoComplete widget
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 *
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class ContactsAutoComplete extends GenericPage
{
    public function __construct(MailPage $mailPage, $contactsAutocompleteFrame)
    {
        parent::__construct($mailPage->getTestCase(), $contactsAutocompleteFrame);
        $this->mailPage = $mailPage;
    }

    /**
     * Clicks on the more results ("Mais resultados...") button for corporated addressbook
     */
    public function clickMoreResults()
    {
        $this->byCssSelector('.ContactsAutocomplete_searchBeyond')->click();
    }

    /**
     * Checks if the contacts list was displayed after typing two character
     * in the recipient field
     *
     * @return boolean
     */
    public function hasContactsListed()
    {
        return $this->isElementPresent('.ContactsAutocomplete_results > .ContactsAutocomplete_oneResult');
    }

    /**
     * Checks if the count was displayed after list of contacts
     *
     * @return boolean
     */
    public function hasContactsCounted()
    {
        return $this->isElementPresent('.ContactsAutocomplete_footer > .ContactsAutocomplete_count');
    }

    /**
     * Clicks on the contact in the contacts list that contains a specified name contacts
     *
     * @param string $contactsText The contact to be searched for
     *
     * @throws \Exception If no contacts entry was found
     */
    public function clickOnContactsListByName($nameContacts)
    {
        $contactDiv = $this->getContactDivByName($nameContacts);
        if ($contactDiv == null) {
            throw new \Exception('Could not find a contact entry with name ' . $nameContacts);
        } else {
            $contactDiv->click();
            $this->waitForAjaxAndAnimations();
        }
    }

    /**
     * Returns a ContactEntry object that represents the item within the contacts
     * list that has a specific name. If there are no entries with the specified
     * name, it returns null.
     *
     * @param string $name The name of contact to be searched for
     *
     * @return contactEntry The entry that contains the specified name of contact, null if
     * no name contacts with the specified name was found
     */
    public function getContactDivByName($name)
    {
        foreach($this->byCssSelectorMultiple('.ContactsAutocomplete_results > .ContactsAutocomplete_oneResult') as $contactsAutocomplete_oneResultDiv) {
            if ($contactsAutocomplete_oneResultDiv->byCssSelector('.ContactsAutocomplete_name')->text() == $name) {
                return $contactsAutocomplete_oneResultDiv;
            }
        }
        return null;
    }

    /**
     * Returns the value of the name contact field
     *
     * @return string
     */
    public function getNameContact()
    {
        return $this->byCssSelector('.ContactsAutocomplete_name')->text();
    }
}
