<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ActionQueue
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Action Queue for long running tasks
 *
 * Method queue for deferred/async execution of Tine 2.0 application actions as defined
 * in the application controllers
 *
 * @package     Tinebase
 * @subpackage  ActionQueue

 *
 */
class Tinebase_ActionQueueLongRun extends Tinebase_ActionQueue
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        parent::__construct();
    }

    protected function _createQueueBackendInstance($className, $options)
    {
        if (!isset($options['queueName'])) {
            $options['queueName'] = Tinebase_ActionQueue_Backend_Redis::QUEUE_NAME;
        }

        $options['queueName'] .= Tinebase_Config::getInstance()->{Tinebase_Config::ACTIONQUEUE}
            ->{Tinebase_Config::ACTIONQUEUE_LONG_RUNNING};

        return parent::_createQueueBackendInstance($className, $options);
    }

    public function cleanDaemonStruct()
    {
        // 5 hours
        $this->_queue->cleanDaemonStruct(5 * 60 * 60);
    }
}
