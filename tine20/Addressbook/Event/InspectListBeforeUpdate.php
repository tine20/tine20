<?php

/**
 * Tine 2.0
 * event class for List inspection before update
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Addressbook_Event_InspectListBeforeUpdate extends Tinebase_Event_Observer_Abstract
{
    /**
     * the List to inspect
     *
     * @var Addressbook_Model_List
     */
    public $observable;

    /**
     * the List to inspect
     *
     * @var Addressbook_Model_List
     */
    public $oldList;
}
