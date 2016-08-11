<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite addressbook module contacts list
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class ContactListItem extends GenericPage
{
    /**
     * @var AddressbookPage The Addressbook page to which this entry belongs
     */
    private $addressbookPage;

    /**
     * Creates a new Addressbook object
     *
     * @param AddressbookPage $addressbookPage The Addressbook page to which this entry belongs
     * @param unknown $contactListItemDiv A reference to the main div of this addressbook contact
     */
    public function __construct(AddressbookPage $addressbookPage, $contactListItemDiv)
    {
        parent::__construct($addressbookPage->getTestCase(), $contactListItemDiv);
        $this->addressbookPage = $addressbookPage;
    }

    /*
     *  Return name of Contact
     */
    public function getNameFromContact()
    {
        return $this->byCssSelector('.WidgetContactList_name')->text();
    }

    /*
     *  Return email of Contact
     */
    public function getEmailFromContact()
    {
        return $this->byCssSelector('.WidgetContactList_email')->text();
    }
}