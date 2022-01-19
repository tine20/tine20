<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * event class for changed list
 *
 * @package     Addressbook
 */
class Addressbook_Event_InspectContactAfterUpdate extends Tinebase_Event_Abstract
{
    /**
     * the list object
     *
     * @var Addressbook_Model_Contact
     */
    public $updatedContact;

    public $record;
}
