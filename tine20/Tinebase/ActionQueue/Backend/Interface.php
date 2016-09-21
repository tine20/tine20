<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
interface Tinebase_ActionQueue_Backend_Interface
{
    /**
     * Constructor
     *
     * @param  array|Zend_Config $config An array having configuration data
     * @return void
     */
    public function __construct($options);

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     */
    public function send($message);
}