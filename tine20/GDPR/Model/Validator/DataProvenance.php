<?php
/**
 * class to validate the DataProvenance attribute on records
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to validate the DataProvenance attribute on records
 * for example on Addressbook_Model_Contact::{GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME}
 *
 * @package     GDPR
 * @subpackage  Model
 */
class GDPR_Model_Validator_DataProvenance implements Zend_Validate_Interface
{

    protected $_messages = [];

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
        if (!empty($value)) {
            if (!GDPR_Controller_DataProvenance::getInstance()->has([$value])) {
                $this->_messages[] = 'provided GDPR data provenance doesn\'t exist';
                return false;
            }
        } else {
            if (GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES ===
                    GDPR_Config::getInstance()->{GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY}) {
                $context = Addressbook_Controller_Contact::getInstance()->getRequestContext();
                if (null !== $context && isset($context['jsonFE'])) {
                    $this->_messages[] = 'GDPR data provenance needs to be provided';
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}