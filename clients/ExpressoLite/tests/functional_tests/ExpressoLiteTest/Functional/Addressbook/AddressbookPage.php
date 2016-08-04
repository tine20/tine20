<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite addressbook module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class AddressbookPage extends GenericPage
{
    /**
     * Clicks on the Catalogo Pessoal
     */
    public function clickPersonalCatalog()
    {
        $this->clickOnMenuCatalog('Catálogo Pessoal');
    }

    /**
     * Clicks on the Catalogo Pessoal
     */
    public function clickCorporateCatalog()
    {
        $this->clickOnMenuCatalog('Catálogo Corporativo');
    }

    /**
     * Clicks on a menu item within the context menu
     *
     * @param string $itemText The text of the item to be clicked
     */
    private function clickOnMenuCatalog($itemText)
    {
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("Menu item with text $itemText was not found");
    }

    /**
     * Returns an array of <li> elements within the context menu
     *
     * @returns array Array of <li> elements within the context menu
     */
    private function getContextMenuItems()
    {
        return $this->byCssSelectorMultiple('.SimpleMenu_list span');
    }

    /**
     * Returns an array containing all catalog entries being displayed on the screen
     *
     * @return array Array of ContactListItem objects
     */
    public function getArrayOfCatalogEntries()
    {
        $entries = array();
        foreach($this->byCssSelectorMultiple('#WidgetContactList_mainSection > .WidgetContactList_item') as $contactListItemDiv) {
            $entries[] = new ContactListItem($this, $contactListItemDiv);
        }
        return $entries;
    }

    /**
     * Returns a CatalogEntry that represents the item of the Catalog list that,
     * contains a specific name. If there are noone with the specified name,
     * it returns null
     *
     * @param string $name The name to be searched for
     *
     * @return CatalogEntry The Catalog that contains the specified name, null if
     * noone with the specified name was found
     */
    public function getCatalogEntryByName($name)
    {
        foreach($this->byCssSelectorMultiple('#WidgetContactList_mainSection > .WidgetContactList_item') as $contactListItemDiv) {
            $entry = new ContactListItem($this, $contactListItemDiv);
            if ($entry->getNameFromContact() == $name) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Returns a CatalogEntry that represents the item of the Catalog list that,
     * contains a specific email. If there are noone with the specified email,
     * it returns null
     *
     * @param string $name The name to be searched for
     *
     * @return CatalogEntry The Catalog that contains the specified name, null if
     * noone with the specified name was found
     */
    public function getCatalogEntryByEmail($email)
    {
        foreach($this->byCssSelectorMultiple('#WidgetContactList_mainSection > .WidgetContactList_item') as $contactListItemDiv) {
            $entry = new ContactListItem($this, $contactListItemDiv);
            if ($entry->getEmailFromContact() == $email) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Returns the string of total entries in Catalog
     */
    public function getCatalogCountEntriesFooter()
    {
        return $this->byCssSelector('#WidgetContactList_footer > #WidgetContactList_loadedCountSpan')->text();
    }

    /*
     * Return the count of total entries in catalog
     */
    public function getCounterTotal()
    {
        // splits the string using the white tab and returns to the search result counter
        $counterList = explode(" ",$this->getCatalogCountEntriesFooter());
        return $counterList[2];
    }

    /**
     * Checks if the AddressBook container was displayed
     *
     * @return boolean
     */
    public function hasAddressbookScreenListed()
    {
        return $this->isElementPresent('#contactListSection');
    }

    /**
     * Checks if this line separator in Address Book exist
     *
     * @return boolean True if the Letter separator is displayed in this entry, false otherwise
     */
    public function hasLetterSeparator($letter)
    {
        return $this->isElementPresent("#letterSeparator_".strtoupper($letter));
    }

    /**
     * Returns the WidgetContactDetails Page Object that represents the contact details
     * currently being displayed in the Addressbook module
     *
     * @return WidgetContactDetails
     */
    public function getWidgetContactDetails()
    {
        return new WidgetContactDetails($this, $this->byCssSelector('#Layout_rightContent'));
    }

    /**
     * Checks if the footer of contacts list was displayed at the end of Contact List and
     * has load more buttom to click
     *
     * @return boolean
     */
    public function hasLoadMoreButton()
    {
        return $this->isElementPresent('#WidgetContactList_footer > #WidgetContactList_loadMoreButton');
    }
}
