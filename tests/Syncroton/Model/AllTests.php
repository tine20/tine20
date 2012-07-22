<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
        $suite->addTestSuite('Syncroton_Model_EventTests');
        $suite->addTestSuite('Syncroton_Model_SyncCollectionTests');
        $suite->addTestSuite('Syncroton_Model_TaskTests');
        
        return $suite;
    }
}
