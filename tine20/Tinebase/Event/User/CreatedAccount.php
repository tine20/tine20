<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * event class for creation of a new account
 *
 * @package     Tinebase
 */
class Tinebase_Event_User_CreatedAccount extends Tinebase_Event_Abstract
{
    /**
     * the account to be deleted
     *
     * @var Tinebase_Model_FullUser
     */
    public $account;
}
