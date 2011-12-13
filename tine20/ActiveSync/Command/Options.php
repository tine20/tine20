<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Command_Options
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
        #header("MS-Server-ActiveSync: 8.3");
        #header("MS-ASProtocolVersions: 2.5,12.0,12.1,14.0,14.1");
        #header("MS-ASProtocolCommands: CreateCollection,DeleteCollection,FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetAttachment,GetHierarchy,GetItemEstimate,ItemOperations,MeetingResponse,MoveCollection,MoveItems,Provision,ResolveRecipients,Ping,SendMail,Search,Settings,SmartForward,SmartReply,Sync,ValidateCert");
    }    
}
