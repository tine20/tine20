<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * base class for all observer events
 *
 * @package     Tinebase
 * @subpackage  Event
 */
abstract class Tinebase_Event_Observer_Abstract extends Tinebase_Event_Abstract
{
    /**
     * @var Tinebase_Model_PersistentObserver
     */
    public $persistentObserver;

    /**
     * @var Tinebase_Record_Interface
     */
    public $observable;
}