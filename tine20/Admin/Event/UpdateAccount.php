<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * event class for updated account
 *
 * @package     Admin
 */
class Admin_Event_UpdateAccount extends Tinebase_Events_Abstract
{
    /**
     * the just added account
     *
     * @var Tinebase_User_Model_FullUser
     */
    public $account;
}