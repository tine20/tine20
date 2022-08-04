<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SubValidate Record(Set)
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Validator_SubValidate implements Zend_Validate_Interface
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
        if ($value instanceof Tinebase_Record_Interface) {
            $value = [$value];
        } elseif (!$value instanceof Tinebase_Record_RecordSet) {
            $this->_messages[] = 'value not a record(set)';
            return false;
        }

        /** @var Tinebase_Record_Interface $val */
        foreach ($value as $val) {
            if (!$val->isValid()) {
                $this->_messages[] = 'subvalidation failed';
                return false;
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
