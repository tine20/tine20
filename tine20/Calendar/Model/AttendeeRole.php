<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * attendee role record
 * 
 * @package     Calendar
 * @property    int     $order
 */
class Calendar_Model_AttendeeRole extends Tinebase_Config_KeyFieldRecord
{
    /**
     * allows to add additional validators in subclasses
     *
     * @var array
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_additionalValidators = [
        'order'               => array('allowEmpty' => true,  'Int'  ),
    ];
}