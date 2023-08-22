<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 */

/**
 * event class for updated account
 *
 * @package     Admin
 */
class Admin_Event_BeforeUpdateAccount extends Tinebase_Event_Abstract
{
    /**
     *
     * @var Tinebase_Model_FullUser
     */
    public $oldAccount;

    /**
     *
     * @var Tinebase_Model_FullUser
     */
    public $newAccount;

}
