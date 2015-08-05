<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_Group_AllTests
{
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite() 
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All Group Tests');
        $suite->addTestSuite('Tinebase_Group_SqlTest');
        $suite->addTestSuite('Tinebase_Group_LdapTest');

        if (TestServer::getInstance()->isPhpunitVersionGreaterOrEquals("3.5.0")) {
            // getMockBuilder() is only supported in phpunit 3.5 and higher 
            $suite->addTestSuite('Tinebase_Group_ActiveDirectoryTest');
        }
        return $suite;
    }
}
