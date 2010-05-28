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
class ActiveSync_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'ActiveSync';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'ActiveSync/js/Application.js',
            'ActiveSync/js/Model.js',
            'ActiveSync/js/DeviceStore.js',
        );
    }
    
    /**
     * authenticate user
     *
     * @param string $_username
     * @param string $_password
     * @param string $_ipAddress
     * @return bool
     */
    public function authenticate($_username, $_password, $_ipAddress)
    {
        $pos = strrchr($_username, '\\');
        
        if($pos !== false) {
            $username = substr(strrchr($_username, '\\'), 1);
        } else {
            $username = $_username;
        }
        
        return Tinebase_Controller::getInstance()->login($username, $_password, $_ipAddress);
    }
    
    /**
     * handle options request
     *
     */
    public function handleOptions()
    {
        // same header like Exchange 2003
        header("MS-Server-ActiveSync: 8.1");
        header("MS-ASProtocolVersions: 2.5,12.0");
        # version 12.1 breaks the Motorola Milestone
        #header("MS-ASProtocolVersions: 2.5,12.0,12.1");
        # no Notify(SMS AUTD)
        #header("MS-ASProtocolCommands: Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping");
        header("MS-ASProtocolCommands: FolderCreate,FolderDelete,FolderSync,FolderUpdate,GetItemEstimate,MeetingResponse,Provision,ResolveRecipients,Ping,SendMail,Search,Settings,SmartReply,Sync");
    }
    
    /**
     * handle post request
     *
     * @param unknown_type $_user
     * @param unknown_type $_deviceId
     * @param unknown_type $_deviceType
     * @param unknown_type $_command
     */
    public function handlePost($_user, $_deviceId, $_deviceType, $_command, $_version)
    {
        #if(!isset($_SERVER['HTTP_X_MS_POLICYKEY']) && $_command != 'Ping') {
        #    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
        #    header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
        #    return;
        #}
        
        // Nokia phones set the devicetype to their IMEI, all other devices to a generic identifier for their platform
        if($_deviceId == $_deviceType && strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 5)) == 'nokia') {
            $_deviceType = 'Nokia';
        }
        
        $userAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : $_deviceType;
        $policyKey = array_key_exists('HTTP_X_MS_POLICYKEY', $_SERVER) ? (int)$_SERVER['HTTP_X_MS_POLICYKEY'] : null; 
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Agent: $userAgent  PolicyKey: $policyKey ASVersion: $_version Command: $_command");
        
        $device = ActiveSync_Controller::getInstance()->getUserDevice($_deviceId, $_deviceType, $userAgent, $_version);
        
        #if($_command != 'Provision' && $_command != 'Ping' && $policyKey != $device->policykey) {
        #    header("HTTP/1.1 449 Retry after sending a PROVISION command");
        #} else {
            if(!class_exists('ActiveSync_Command_' . $_command)) {
                throw new Exception('unsupported command ' . $_command);
            }
    
            $className = 'ActiveSync_Command_' . $_command;
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " class name: $className");
            $command = new $className($device);
            
            $command->handle();
            
            header("MS-Server-ActiveSync: 8.1");
            
            $command->getResponse();            
        #}
    }
}