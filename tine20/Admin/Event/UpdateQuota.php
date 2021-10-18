<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 */

/**
 * event class for updated quota
 *
 * @package     Admin
 * @subpackage  Event
 */
class Admin_Event_UpdateQuota extends Tinebase_Event_Abstract
{
    /**
     * application
     *
     * @var string
     */
    public $application;

    /**
     * record data
     *
     */
    public $recordData;

    /**
     * additional data
     * 
     * @var array
     */
    public $additionalData;
}
