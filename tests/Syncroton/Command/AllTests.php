<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

class Syncroton_Command_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncroton all ActiveSync command tests');
        
        $suite->addTestSuite('Syncroton_Command_FolderCreateTests');
        $suite->addTestSuite('Syncroton_Command_FolderDeleteTests');
        $suite->addTestSuite('Syncroton_Command_FolderSyncTests');
        $suite->addTestSuite('Syncroton_Command_GetItemEstimateTests');
        $suite->addTestSuite('Syncroton_Command_ItemOperationsTests');
        $suite->addTestSuite('Syncroton_Command_MoveItemsTests');
        $suite->addTestSuite('Syncroton_Command_PingTests');
        $suite->addTestSuite('Syncroton_Command_ProvisionTests');
        $suite->addTestSuite('Syncroton_Command_SearchTests');
        $suite->addTestSuite('Syncroton_Command_SettingsTests');
        $suite->addTestSuite('Syncroton_Command_SyncTests');
        
        return $suite;
    }
}
