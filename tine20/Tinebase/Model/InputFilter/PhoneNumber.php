<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  InputFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Phone Number without leading plus sign
 *
 * @package     Tinebase
 * @subpackage  InputFilter
 */
class Tinebase_Model_InputFilter_PhoneNumber implements Zend_Filter_Interface
{
    public function filter($value)
    {
        return Addressbook_Model_Contact::normalizeTelephoneNum($value);
    }
}
