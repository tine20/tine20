<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 */

/**
 * event class for added account
 *
 * @package     Admin
 */
class Admin_Event_BeforeAddAccount extends Tinebase_Event_Abstract
{
    /**
     * the account that ready to be created
     *
     * @var Tinebase_Model_FullUser
     */
    public $account;

}
