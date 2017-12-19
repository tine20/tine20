<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * event class for deleted accounts (this is fired before the deletion)
 *
 * @package     Admin
 */
class Admin_Event_BeforeDeleteAccount extends Tinebase_Event_Abstract
{
    /**
     * the account to be deleted
     *
     * @var Tinebase_Model_FullUser
     */
    public $account;
}
