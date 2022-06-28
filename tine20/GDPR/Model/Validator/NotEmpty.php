<?php
/**
 * class to help validate the DataProvenance attribute on records
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to help validate the DataProvenance attribute on records
 * for example on Addressbook_Model_Contact::{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME}
 * this validator makes sure even empty values will be passed to the following validators
 *
 * @package     GDPR
 * @subpackage  Model
 */
class GDPR_Model_Validator_NotEmpty extends Zend_Validate_NotEmpty
{
    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        return true;
    }
}
