<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * factory class for av scanner
 */
class Tinebase_FileSystem_AVScan_Factory
{
    const MODE_OFF = 'off';
    const MODE_QUAHOG = 'quahog';

    protected static $_scanners = [];

    public static function registerScanner($_name, $_class)
    {
        static::$_scanners[$_name] = $_class;
    }

    /**
     * @return Tinebase_FileSystem_AVScan_Interface
     * @throws Tinebase_Exception_NotImplemented
     */
    public static function getScanner()
    {
        $mode = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE};
        switch ($mode) {
            case self::MODE_QUAHOG:
                return new Tinebase_FileSystem_AVScan_Quahog();
            default:
                if (isset(static::$_scanners[$mode])) {
                    return new static::$_scanners[$mode]();
                }
        }

        throw new Tinebase_Exception_NotImplemented('no av scanner for mode "' . $mode . '" implemented');
    }
}