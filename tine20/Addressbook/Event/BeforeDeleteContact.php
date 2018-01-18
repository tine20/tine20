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
 * event class for deleted contact - fired before deletion in \Addressbook_Controller_Contact::_deleteRecord
 *
 * @package     Addressbook
 */
class Addressbook_Event_BeforeDeleteContact extends Tinebase_Event_Observer_Abstract
{
    /**
     * the list object
     *
     * @var Addressbook_Model_Contact
     */
    public $observable;
}
