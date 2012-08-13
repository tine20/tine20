<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Model_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncroton all model tests');
        
        $suite->addTestSuite('Syncroton_Model_ContactTests');
        $suite->addTestSuite('Syncroton_Model_EmailTests');
        $suite->addTestSuite('Syncroton_Model_EventTests');
        $suite->addTestSuite('Syncroton_Model_FileReferenceTests');
        $suite->addTestSuite('Syncroton_Model_PolicyTests');
        $suite->addTestSuite('Syncroton_Model_SyncCollectionTests');
        $suite->addTestSuite('Syncroton_Model_TaskTests');
        
        return $suite;
    }
}
