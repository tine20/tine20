<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * All Addressbook tests
 *
 * @package     Addressbook
 */
class Addressbook_Convert_AllTests
{
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('All Addressbook Converter tests');

        $suite->addTestSuite('Addressbook_Convert_Contact_StringTest');
        $suite->addTest(Addressbook_Convert_Contact_VCard_AllTests::suite());

        return $suite;
    }
}
