<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * http frontend
 *
 * @package     ActiveSync
 * @subpackage  Frontend
 */
class ActiveSync_Frontend_Http extends Tinebase_Frontend_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'ActiveSync';
    
    /**
     * authenticate user
     *
     * @param unknown_type $_username
     * @param unknown_type $_password
     * @param unknown_type $_ipAddress
     * @return unknown
     */
    public function authenticate($_username, $_password, $_ipAddress)
    {
        return ActiveSync_Controller::getInstance()->authenticate($_username, $_password, $_ipAddress);
    }
    
    /**
     * handle options request
     *
     */
    public function handleOptions()
    {
        // same header like Exchange 2003
        header("MS-Server-ActiveSync: 6.5.7638.1");
        header("MS-ASProtocolVersions: 2.5");
        # no Notify(SMS AUTD)
        #header("MS-ASProtocolCommands: Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping");
        header("MS-ASProtocolCommands: FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetItemEstimate,Ping,Provision,SendMail,Settings,SmartReply,Sync");
    }
    
    /**
     * handle post request
     *
     * @param unknown_type $_user
     * @param unknown_type $_deviceId
     * @param unknown_type $_deviceType
     * @param unknown_type $_command
     */
    public function handlePost($_user, $_deviceId, $_deviceType, $_command)
    {
        if(!isset($_SERVER['HTTP_MS_ASPROTOCOLVERSION'])) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " MS-ASPROTOCOLVERSION missing (" . $_command. ')');
            header("HTTP/1.1 400 header MS-ASPROTOCOLVERSION not found");
            return;
        }
        
        #if(!isset($_SERVER['HTTP_X_MS_POLICYKEY']) && $_command != 'Ping') {
        #    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
        #    header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
        #    return;
        #}
        
        // Nokia phones set the devicetype to their IMEI, all other devices to a generic identifier for their platform
        if($_deviceId == $_deviceType && strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 5)) == 'nokia') {
            $_deviceType = 'Nokia';
        }
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $asVersion = $_SERVER['HTTP_MS_ASPROTOCOLVERSION'];
        $policyKey = (int)$_SERVER['HTTP_X_MS_POLICYKEY']; 
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Agent: $userAgent  PolicyKey: $policyKey ASVersion: $asVersion Command: $_command");
        
        $device = ActiveSync_Controller::getInstance()->getUserDevice($_deviceId, $_deviceType, $userAgent, $asVersion);
        
        #if($_command != 'Provision' && $_command != 'Ping' && $policyKey != $device->policykey) {
        #    header("HTTP/1.1 449 Retry after sending a PROVISION command");
        #} else {
            if(!class_exists('ActiveSync_Command_' . $_command)) {
                throw new Exception('unsupported command ' . $_command);
            }
    
            $className = 'ActiveSync_Command_' . $_command;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " class name: $className");
            $command = new $className($device);
            
            $command->handle();
            
            header("MS-Server-ActiveSync: 6.5.7638.1");
            
            $command->getResponse();            
        #}
    }
}