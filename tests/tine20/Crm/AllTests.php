<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Crm_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm All Tests');
        $suite->addTest(Crm_Backend_AllTests::suite());
        $suite->addTest(Crm_Export_AllTests::suite());
        $suite->addTestSuite('Crm_ControllerTest');
        $suite->addTestSuite('Crm_JsonTest');
        $suite->addTestSuite('Crm_NotificationsTests');
        $suite->addTestSuite('Crm_Acl_RolesTest');
        $suite->addTestSuite('Crm_Import_CsvTest');
        return $suite;
    }
}
