<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Class Tinebase_Log_Formatter_Json
 *
 * @todo support timelog
 */
class Tinebase_Log_Formatter_Json extends Tinebase_Log_Formatter
{
    /**
     * Formats data into a single json_encoded line to be written by the writer.
     *
     * @param array $event event data
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        $logruntime = $this->_getLogRunTime(false);
        $logdifftime = $this->_getLogDiffTime(false);
        $event = array_merge([
            'user' => self::getUsername(),
            'transaction_id' => self::$_transactionId,
            'request_id' => self::$_requestId,
            'logdifftime' => $logdifftime,
            'logruntime' => $logruntime,
            // TODO add method
            // 'method' => self::$_method
        ], $event);

        if (isset($event['message'])) {
            $event['message'] = str_replace($this->_search, $this->_replace, $event['message']);
        }

        return @json_encode($event,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION)
            . PHP_EOL;
    }
}
