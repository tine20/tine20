<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Tests for Admin app
 *
 * @package     Admin
 */
class Admin_Import_DemoDataTest
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Admin Demo Data Test');
        $suite->addTestSuite('Admin_Import_UserTest');
        $suite->addTestSuite('Admin_Import_GroupTest');
        $suite->addTestSuite('Admin_Import_RoleTest');

        return $suite;
    }
}

