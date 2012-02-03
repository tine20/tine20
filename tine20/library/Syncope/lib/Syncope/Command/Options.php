<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync http options request
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_Options
{
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        // same header like Exchange 2003
        header("MS-Server-ActiveSync: 8.3");
        header("MS-ASProtocolVersions: 2.5,12.0");
        header("MS-ASProtocolCommands: CreateCollection,DeleteCollection,FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetAttachment,GetItemEstimate,MeetingResponse,MoveCollection,MoveItems,Provision,ResolveRecipients,Ping,SendMail,Search,Settings,SmartForward,SmartReply,Sync");
        
        // same header like Exchange 2xxx???
        #header('MS-Server-ActiveSync:  14.00.0536.000');
        #header("MS-ASProtocolVersions: 2.5,12.0,12.1,14.0,14.1");
        #header("MS-ASProtocolCommands: CreateCollection,DeleteCollection,FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetAttachment,GetHierarchy,GetItemEstimate,ItemOperations,MeetingResponse,MoveCollection,MoveItems,Provision,ResolveRecipients,Ping,SendMail,Search,Settings,SmartForward,SmartReply,Sync,ValidateCert");
        #header('MS-ASProtocolRevisions: 12.1r1');
    }    
}
