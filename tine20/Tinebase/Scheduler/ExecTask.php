<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Exec Task class that executes a shell cmd
 *
 * add job to tine20_scheduler_task like that:
 *  config: {"cron":"0 4 * * *","callables":[{"controller":"Tinebase_Scheduler_ExecTask","method":"shellExec","args":["/my/shell/command"]}]}
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 */
class Tinebase_Scheduler_ExecTask implements Tinebase_Controller_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Scheduler_ExecTask
     */
    private static $_instance = NULL;

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * the singleton pattern
     *
     * @return Tinebase_Scheduler_ExecTask
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Scheduler_ExecTask;
        }

        return self::$_instance;
    }

    /**
     * @param $cmd
     * @return bool
     */
    public function shellExec($cmd)
    {
        exec($cmd, $output, $returnValue);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Exec command "' . $cmd . '" return value: ' . $returnValue . ' exec output: ' . join(PHP_EOL, (array)$output));
        if (0 !== $returnValue) {
            Tinebase_Exception::sendExceptionToSentry(new Tinebase_Exception('exec command ' . $cmd . ' failed!'));
        }

        return 0 === $returnValue;
    }
}
