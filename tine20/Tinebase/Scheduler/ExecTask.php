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
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Scheduler_ExecTask
{
    public static function shellExec($cmd)
    {
        exec($cmd, $output, $returnValue);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' exec return value: ' . $returnValue . ' exec output: ' . join(PHP_EOL, (array)$output));

        return 0 === $returnValue;
    }
}