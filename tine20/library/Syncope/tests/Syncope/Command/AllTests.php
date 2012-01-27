<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

class Syncope_Command_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncope all ActiveSync command tests');
        
        $suite->addTestSuite('Syncope_Command_FolderCreateTests');
        $suite->addTestSuite('Syncope_Command_FolderDeleteTests');
        $suite->addTestSuite('Syncope_Command_FolderSyncTests');
        $suite->addTestSuite('Syncope_Command_GetItemEstimateTests');
        $suite->addTestSuite('Syncope_Command_ItemOperationsTests');
        $suite->addTestSuite('Syncope_Command_MoveItemsTests');
        $suite->addTestSuite('Syncope_Command_PingTests');
        $suite->addTestSuite('Syncope_Command_ProvisionTests');
        $suite->addTestSuite('Syncope_Command_SearchTests');
        $suite->addTestSuite('Syncope_Command_SettingsTests');
        $suite->addTestSuite('Syncope_Command_SyncTests');
        
        return $suite;
    }
}
