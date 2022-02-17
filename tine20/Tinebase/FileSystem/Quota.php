<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class encapsulating filesystem quota configuration
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 */
class Tinebase_FileSystem_Quota
{
    /**
     * @var Tinebase_Config_Struct
     */
    protected static $_quotaConfig = null;

    /**
     * for unit testing
     */
    public static function clearConfigCache()
    {
        self::$_quotaConfig = null;
    }

    /**
     * @return Tinebase_Config_Struct
     */
    public static function getConfig()
    {
        if (null === static::$_quotaConfig) {
            self::$_quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};
        }
        return self::$_quotaConfig;
    }

    /**
     * @return int
     */
    public static function getRootQuotaBytes()
    {
        return self::getConfig()->{Tinebase_Config::QUOTA_FILESYSTEM_TOTALINMB} * 1024 * 1024;
    }

    /**
     * @return int
     */
    public static function getPersonalQuotaBytes()
    {
        return self::getConfig()->{Tinebase_Config::QUOTA_TOTALBYUSERINMB} * 1024 * 1024;
    }

    /**
     * check getRootQuotaBytes === 0 first! (unlimited)
     *
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getRootFreeBytes()
    {
        $freeBytes = self::getRootQuotaBytes() - self::getRootUsedBytes();
        if ($freeBytes < 0) {
            return 0;
        }
        return $freeBytes;
    }

    /**
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getRootUsedBytes()
    {
        if (self::getConfig()->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
            $totalUsage = intval(Tinebase_Application::getInstance()->getApplicationState(
                Tinebase_Core::getTinebaseId(), Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE));
        } else {
            $totalUsage = intval(Tinebase_Application::getInstance()->getApplicationState(
                Tinebase_Core::getTinebaseId(), Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE));
        }

        return $totalUsage;
    }
}
