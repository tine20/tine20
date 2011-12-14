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
        
        return Tinebase_Controller::getInstance()->login($username, $_password, $_ipAddress, 'TineActiveSync');
    }
    
    /**
     * handle options request
     *
     */
    public function handleOptions()
    {
        $command = new ActiveSync_Command_Options();
        
        $command->getResponse();            
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
        $request = new Zend_Controller_Request_Http();
        
        // Nokia phones set the devicetype to their IMEI, all other devices to a generic identifier for their platform
        if($_deviceId == $_deviceType && strtolower(substr($request->getServer('HTTP_USER_AGENT'), 0, 5)) == 'nokia') {
            $_deviceType = 'Nokia';
        }
        
        $userAgent = $request->getServer('HTTP_USER_AGENT', $_deviceType);
        $policyKey = $request->getServer('HTTP_X_MS_POLICYKEY'); 
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Agent: $userAgent  PolicyKey: $policyKey ASVersion: $_version Command: $_command");
        
        $device = ActiveSync_Controller::getInstance()->getUserDevice($_deviceId, $_deviceType, $userAgent, $_version);
        
        if(!class_exists('ActiveSync_Command_' . $_command)) {
            throw new Exception('unsupported command ' . $_command);
        }
    
        $className = 'ActiveSync_Command_' . $_command;
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " class name: " . print_r($_SERVER, true));
        
        if ($request->getServer('CONTENT_TYPE') == 'application/vnd.ms-sync.wbxml') {
            // decode wbxml request
            try {
                $decoder = new Wbxml_Decoder(fopen("php://input", "r"));
                $requestBody = $decoder->decode();
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " xml request: " . $requestBody->saveXML());
            } catch(Wbxml_Exception_UnexpectedEndOfFile $e) {
                $requestBody = NULL;
            }
        } else {
            $requestBody = fopen("php://input", "r");
        }
        
        try {
            $command = new $className($requestBody, $device, $policyKey);
            
            $command->handle();
            
            header("MS-Server-ActiveSync: 8.3");
            
            $response = $command->getResponse();            
        } catch (ActiveSync_Exception_PolicyKeyMissing $asepkm) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) 
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " X-MS-POLICYKEY missing (" . $_command. ')');
            header("HTTP/1.1 400 header X-MS-POLICYKEY not found");
            return;
        } catch (ActiveSync_Exception_ProvisioningNeeded $asepn) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " provisioning needed");
            header("HTTP/1.1 449 Retry after sending a PROVISION command");
            return;
        }
        
        Tinebase_Controller::getInstance()->logout($request->getClientIp());
        
        if ($request->getServer('CONTENT_TYPE') == 'application/vnd.ms-sync.wbxml') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " xml response: " . $response->saveXML());
            
            $outputStream = fopen("php://temp", 'r+');
            
            
            $encoder = new Wbxml_Encoder($outputStream, 'UTF-8', 3);
            $encoder->encode($response);
            
            header("Content-Type: application/vnd.ms-sync.wbxml");
            
            rewind($outputStream);
            fpassthru($outputStream);
        }
    }
}
