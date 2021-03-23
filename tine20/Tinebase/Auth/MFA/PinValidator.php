<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Pin Policy Validator
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_PinValidator implements Zend_Validate_Interface
{
    protected $message;

    public function isValid($value)
    {
        if (empty($value)) {
            return true;
        }
        if (preg_match('/[^0-9]/', (string) $value)) {
            $this->message = 'Only numbers are allowed for PINs';
            return false;
        }

        if (strlen((string) $value) < Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PIN_MIN_LENGTH)) {
            $this->message = 'PIN too short';
            return false;
        }

        return true;
    }

    public function getMessages()
    {
        return [$this->message];
    }
}
