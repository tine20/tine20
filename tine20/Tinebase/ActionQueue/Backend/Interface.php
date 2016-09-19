<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param  array|Zend_Config $options An array having configuration data
     */
    public function __construct($options);

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     */
    public function send($message);

    /**
     * return queue length
     *
     * @return int the queue length
     */
    public function getQueueSize();

    /**
     * wait for a new job in queue
     *
     * @return mixed false on timeout or job id
     */
    public function waitForJob();

    /**
     * get one job from the queue
     *
     * @param  integer  $jobId  the id of the job
     * @throws RuntimeException
     * @return string           the job
     */
    public function receive($jobId);

    /**
     * Delete a job from the queue
     *
     * @param  string  $jobId  the id of the job
     */
    public function delete($jobId);
}