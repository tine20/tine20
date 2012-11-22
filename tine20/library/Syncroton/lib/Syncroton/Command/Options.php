<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync http options request
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_Options
{
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getHeaders()
    {
        // same header like Exchange 2xxx???
        return array(
            'MS-Server-ActiveSync'  => '14.00.0536.000',
            'MS-ASProtocolVersions' => '2.5,12.0,12.1,14.0,14.1',
            'MS-ASProtocolCommands' => 'CreateCollection,DeleteCollection,FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetAttachment,GetHierarchy,GetItemEstimate,ItemOperations,MeetingResponse,MoveCollection,MoveItems,Provision,ResolveRecipients,Ping,SendMail,Search,Settings,SmartForward,SmartReply,Sync,ValidateCert'
        );
    }    
}
