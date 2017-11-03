<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Scheduler_Mock
{
    protected static $_ran = false;

    public static function run()
    {
        static::$_ran = true;
    }

    public static function didRun()
    {
        return static::$_ran;
    }
}