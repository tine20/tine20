<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * event class for changed list
 *
 * @package     Admin
 */
class Addressbook_Event_ChangeList extends Tinebase_Event_Abstract
{
    /**
     * the list object
     *
     * @var Addressbook_Model_List
     */
    public $list;
}
