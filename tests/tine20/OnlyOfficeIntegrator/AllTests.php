<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     OnlyOfficeIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * All OnlyOfficeIntegrator tests
 *
 * @package     OnlyOfficeIntegrator
 */
class OnlyOfficeIntegrator_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('All OnlyOfficeIntegrator tests');

        $suite->addTestSuite(OnlyOfficeIntegrator_ControllerTests::class);
        $suite->addTestSuite(OnlyOfficeIntegrator_FilemanagerTests::class);
        $suite->addTestSuite(OnlyOfficeIntegrator_JsonTests::class);

        return $suite;
    }
}
