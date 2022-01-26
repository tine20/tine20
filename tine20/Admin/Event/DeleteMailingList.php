<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * event class for changed list
 *
 * @package     Admin
 */
class Admin_Event_DeleteMailingList extends Tinebase_Event_Abstract
{
    /**
     * the list id
     *
     * @var string
     */
    public $listId;
}
