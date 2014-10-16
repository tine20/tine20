<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Tinebase_Session_Validator_AccountStatus
 *
 * @package    Tinebase
 * @subpackage Session
 */
class Tinebase_Session_Validator_AccountStatus extends Zend_Session_Validator_Abstract
{

    /**
     * Setup() - this method will get the current users id and store it in the session
     * as 'valid data'
     *
     * @return void
     */
    public function setup()
    {
        $this->setValidData(Tinebase_Core::getUser()->accountId);
    }

    /**
     * Validate() - this method will determine if the account status is ENABLED
     *
     * @return bool
     */
    public function validate()
    {
        $currentAccount = Tinebase_User::getInstance()->getFullUserById($this->getValidData());

        return !in_array(
            $currentAccount->accountStatus,
            array(
                Tinebase_Model_User::ACCOUNT_STATUS_DISABLED,
                Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED
            )
        );
    }
}
