<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * event for setting group memberships of an user
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Event_SetGroupMemberships extends Tinebase_Event_Abstract
{
    /**
     * the user id
     *
     * @var string
     */
    public $user;

    /**
     * list of ids of added groupmemberships
     *
     * @var array
     */
    public $addedMemberships = array();
    
    /**
     * list of ids of removed groupmemberships
     *
     * @var array
     */
    public $removedMemberships = array();
}
