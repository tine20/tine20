<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Action Queue Test Backend
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 */
class Tinebase_ActionQueue_Backend_Test implements Tinebase_ActionQueue_Backend_Interface
{
    public static $_hasAsyncBackend = true;
    public static $_peekJobId = false;
    public static $_queueSize = 0;
    public static $_daemonStructSize = 0;
    public static $_daemonStructSizeCall = null;
    public static $_queueKeys = [];

    /**
     * Constructor
     *
     * @param  array|Zend_Config $options An array having configuration data
     */
    public function __construct($options)
    {
    }

    /**
     * Send a message to the queue
     *
     * @param  mixed $message Message to send to the active queue
     */
    public function send($message)
    {
        // TODO: Implement send() method.
    }

    /**
     * @return boolean|string
     */
    public function peekJobId()
    {
        return static::$_peekJobId;
    }

    /**
     * return queue length
     *
     * @return int the queue length
     */
    public function getQueueSize()
    {
        return static::$_queueSize;
    }

    /**
     * wait for a new job in queue
     *
     * @return mixed false on timeout or job id
     */
    public function waitForJob()
    {
        // TODO: Implement waitForJob() method.
    }

    /**
     * get one job from the queue
     *
     * @param  integer $jobId the id of the job
     * @throws RuntimeException
     * @return array           the job
     */
    public function receive($jobId)
    {
        // TODO: Implement receive() method.
    }

    /**
     * Delete a job from the queue
     *
     * @param  string $jobId the id of the job
     */
    public function delete($jobId)
    {
        // TODO: Implement delete() method.
    }

    /**
     * check if the backend is async
     *
     * @return boolean true if queue backend is async
     */
    public function hasAsyncBackend()
    {
        return static::$_hasAsyncBackend;
    }

    public function getDaemonStructSize()
    {
        if (null !== static::$_daemonStructSizeCall) (static::$_daemonStructSizeCall)();
        return static::$_daemonStructSize;
    }

    public function getData()
    {
        return [];
    }

    public function getQueueKeys()
    {
        return  static::$_queueKeys;
    }

    public function getDaemonStructKeys()
    {
        return [];
    }

    public function iterateAllData()
    {
        return false;
    }
}
