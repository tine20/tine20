<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * result of an av scan
 */
class Tinebase_FileSystem_AVScan_Result
{
    const RESULT_OK = \Xenolope\Quahog\Client::RESULT_OK;
    const RESULT_FOUND = \Xenolope\Quahog\Client::RESULT_FOUND;
    const RESULT_ERROR = \Xenolope\Quahog\Client::RESULT_ERROR;
    const RESULT_RELOADING = 'RELOADING';

    public $result;
    public $message;

    public function __construct($result, $message)
    {
        if (self::RESULT_OK !== $result && self::RESULT_FOUND !== $result) {
            $this->result = self::RESULT_ERROR;
        } else {
            $this->result = $result;
        }

        $this->message = $message;
    }
}