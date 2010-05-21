<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * syncable user backend interface
 *
 * @package     Tinebase
 * @subpackage  User
 */
interface Tinebase_User_Interface_SyncAble
{
    /**
     * get user by login name
     *
     * @param   string  $_property
     * @param   string  $_accountId
     * @return Tinebase_Model_User the user object
     */
    public function getSyncAbleUserByProperty($_property, $_accountId, $_accountClass = 'Tinebase_Model_User');

    /**
     * updates an existing user in sql backend only
     *
     * @param  Tinebase_Model_FullUser  $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateLocalUser(Tinebase_Model_FullUser $_account);
    
    /**
     * adds a new user to local sql backend only
     *
     * @param  Tinebase_Model_FullUser  $_account
     * @return Tinebase_Model_FullUser
     */
    public function addLocalUser(Tinebase_Model_FullUser $_account);
}
