<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Tinebase_Session_Validator_MaintenanceMode
 *
 * @package    Tinebase
 * @subpackage Session
 */
class Tinebase_Session_Validator_MaintenanceMode extends Zend_Session_Validator_Abstract
{

    /**
     * @return void
     */
    public function setup()
    {
        $this->setValidData(Tinebase_Core::getUser()->accountId);
    }

    /**
     * validate if user is allowed to use the software in maintenance mode
     *
     * @return bool
     */
    public function validate()
    {
        if (Tinebase_Core::inMaintenanceMode()) {
            $currentAccount = Tinebase_User::getInstance()->getFullUserById($this->getValidData());
            if (!$currentAccount->hasRight('Tinebase', Tinebase_Acl_Rights::MAINTENANCE)) {
                return false;
            }
        }

        return true;
    }
}
