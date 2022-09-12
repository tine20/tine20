<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 */

/**
 * event class for updated quota
 *
 * @package     Tinebase
 * @subpackage  Event
 */
class Tinebase_Event_Notification extends Tinebase_Event_Abstract
{
    /**
     * updater
     *
     * @var string
     */
    public $updater;

    /**
     * recipients
     *
     * @var array
     */
    public $recipients;

    /**
     * subject
     *
     * @var string
     */
    public $subject;

    /**
     * messagePlain
     *
     * @var string
     */
    public $messagePlain;
}
