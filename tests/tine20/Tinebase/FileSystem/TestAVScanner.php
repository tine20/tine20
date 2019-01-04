<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * test av scanner class
 */
class Tinebase_FileSystem_TestAVScanner implements Tinebase_FileSystem_AVScan_Interface
{
    public static $desiredResult = null;

    public function __construct()
    {
        if (null === static::$desiredResult) {
            static::$desiredResult =
                new Tinebase_FileSystem_AVScan_Result(Tinebase_FileSystem_AVScan_Result::RESULT_OK, null);
        }
    }

    /**
     * @param resource $handle
     * @return Tinebase_FileSystem_AVScan_Result
     */
    public function scan($handle)
    {
        return clone static::$desiredResult;
    }

    /**
     * @return bool
     */
    public function update()
    {
        return true;
    }
}