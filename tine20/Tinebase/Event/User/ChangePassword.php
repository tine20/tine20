<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 */

/**
 * event class for change password
 *
 * @package     Tinebas
 * @subpackage  Event
 */
class Tinebase_Event_User_ChangePassword extends Tinebase_Event_Abstract
{
    /**
     * id of Tinebase_Model_FullUser
     *
     */
    public $userId;

    /**
     * new password
     * 
     */
    public $password;
}
