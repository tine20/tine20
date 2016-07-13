<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite addressbook Detail
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class WidgetContactDetails extends GenericPage
{
    /**
     * @var AddressbookPage The Addressbook page to which this entry belongs
     */
    private $addressbookPage;

    /**
     * Creates a new WidgetContactDetails object
     *
     * @param AddressbookPage $addressbookPage The Addressbook page in which the contact details are being displayed
     * @param unknown $rightSection The section element where the contact details is being displayed
     */
    public function __construct(AddressbookPage $addressbookPage, $rightSection)
    {
        parent::__construct($addressbookPage->getTestCase(), $rightSection);
        $this-> addressbookPage = $addressbookPage;
    }

    /*
     *  Returns name of Contact
     */
    public function getName()
    {
        return $this->byCssSelector('.WidgetContactDetails_name')->text();
    }

    /*
     *  Returns Email of Contact
     */
    public function getEmail()
    {
        return $this->byCssSelector('.WidgetContactDetails_email')->text();
    }

    /*
     *  Returns phone of Contact
     */
    public function getPhone()
    {
        return $this->byCssSelector('.WidgetContactDetails_phone')->text();
    }

    /*
     *  Check if has image of contact
     */
    public function hasImage()
    {
        return $this->isElementPresent('#WidgetContactDetails_mugshot');
    }

    /*
     * Returns a .WidgetContactDetails_otherFieldRow HTML element that has an
     * specific label
     * @param string $fieldLabel The label of the field whose value will be
     * returned
     * @returns unknown The .WidgetContactDetails_otherFieldRow HTML element that has
     * the specified field label, or null if there are no fields with that label
     */
  private function getOtherFieldRowByLabel($fieldLabel)
  {
    $otherFieldRows = $this->byCssSelectorMultiple('#WidgetContactDetails_otherFieldsDiv > .WidgetContactDetails_otherFieldRow');
    foreach($otherFieldRows as $row) {
      $currFieldLabel = $row->byCssSelector('.WidgetContactDetails_otherFieldLabel')->text();
      if ($currFieldLabel == $fieldLabel) {
        return $row;
      }
    }
    return null;
  }

    /*
    * Returns the value of a field being displayed in the 'others' field section.
    *
    * @param string $fieldLabel The label of the field whose value will be returned
    * @return string The value of the input of the specified field
    */
    public function getOtherFieldValue($fieldLabel)
    {
        $otherFieldRow = $this->getOtherFieldRowByLabel($fieldLabel);
        return $otherFieldRow->byCssSelector('.WidgetContactDetails_otherFieldValue')->attribute('value');
    }

    /*
     * Checks if a field in the 'other fields' section is readonly.
     *
     * @param string $fieldLabel The label of the field whose to be checked
     * @returns boolean true if the field is readonly, false otherwise
     */
    public function isReadonlyField($fieldLabel)
    {
        $otherFieldRow = $this->getOtherFieldRowByLabel($fieldLabel);
        return $otherFieldRow->byCssSelector('.WidgetContactDetails_otherFieldValue')->attribute('readonly');
    }
}