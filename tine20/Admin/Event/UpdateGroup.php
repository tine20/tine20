<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * event class for updated group
 *
 * @package     Admin
 */
class Admin_Event_UpdateGroup extends Tinebase_Event_Abstract
{
    /**
     * the group object
     *
     * @var Tinebase_Model_Group
     */
    public $group;

}
