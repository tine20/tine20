<?php
/**
 * Singleton Trait for Controllers
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Singleton Trait for Controllers
 *
 * private static $_instance;
 * public static getInstance();
 * public static destroyInstance();
 * private function __construct();
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
trait Tinebase_Controller_SingletonTrait
{
    /**
     * holds the instance of the singleton
     *
     * @var self
     */
    private static $_instance = null;

    /**
     * the singleton pattern
     *
     * @return self
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {}

    /**
     * don't clone. Use the singleton.
     *
     */
    protected function __clone() {}
}