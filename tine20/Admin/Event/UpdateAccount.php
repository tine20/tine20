<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * event class for updated account
 *
 * @package     Admin
 * @subpackage  Event
 */
class Admin_Event_UpdateAccount extends Tinebase_Event_Abstract
{
    /**
     * the updated account
     *
     * @var Tinebase_Model_FullUser
     */
    public $account;

    /**
     * the old account
     *
     * @var Tinebase_Model_FullUser
     */
    public $oldAccount;
}
