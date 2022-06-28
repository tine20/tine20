<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Bookmarks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * 
 */



class Bookmarks_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Bookmarks All Tests');
        
        //$suite->addTestSuite('Bookmarks_JsonTest');
        $suite->addTestSuite('Bookmarks_ImportTest');
        //$suite->addTestSuite('Bookmarks_ControllerTest');
        return $suite;
    }
}
