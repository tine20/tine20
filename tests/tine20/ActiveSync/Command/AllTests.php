<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

/**
 * All ActiveSync command tests
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All ActiveSync command tests');
        
        $suite->addTestSuite('ActiveSync_Command_PingTests');
        $suite->addTestSuite('ActiveSync_Command_SyncTests');
        
        return $suite;
    }
}
