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
        $suite->addTestSuite('Syncroton_Command_FolderUpdateTests');
        $suite->addTestSuite('Syncroton_Command_GetAttachmentTests');
        $suite->addTestSuite('Syncroton_Command_GetItemEstimateTests');
        $suite->addTestSuite('Syncroton_Command_ItemOperationsTests');
        $suite->addTestSuite('Syncroton_Command_MeetingResponseTests');
        $suite->addTestSuite('Syncroton_Command_MoveItemsTests');
        $suite->addTestSuite('Syncroton_Command_PingTests');
        $suite->addTestSuite('Syncroton_Command_ProvisionTests');
        $suite->addTestSuite('Syncroton_Command_SearchTests');
        $suite->addTestSuite('Syncroton_Command_SettingsTests');
        $suite->addTestSuite('Syncroton_Command_SmartForwardTests');
        $suite->addTestSuite('Syncroton_Command_SendMailTests');
        $suite->addTestSuite('Syncroton_Command_SyncTests');
        
        return $suite;
    }
}
