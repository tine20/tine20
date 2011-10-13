<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert a generic vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
interface Addressbook_Convert_Contact_VCard_Interface
{
    /**
     * converts vcard to Addressbook_Model_Contact
     * 
     * @param  Sabre_VObject_Component|stream|string  $_blob   the vcard to parse
     * @param  Tinebase_Record_Abstract               $_model  update existing contact
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null);
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_model
     * @return string
     */
    public function fromTine20Model(Addressbook_Model_Contact $_model);    
}
