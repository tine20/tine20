<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * http server
 *
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_Http implements Tinebase_Server_Interface
{
    /**
     * handler for ActiveSync requests
     * 
     * @return boolean
     */
    public function handle()
    {
        try {
            Tinebase_Core::initFramework();
        } catch (Zend_Session_Exception $exception) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid session. Delete session cookie.');
            Zend_Session::expireSessionCookie();
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is ActiveSync request.');
        
        try {
            $activeSync = Tinebase_Application::getInstance()->getApplicationByName('ActiveSync');
        } catch (Tinebase_Exception_NotFound $e) {
            // activeSync not installed
            header('HTTP/1.1 403 ActiveSync not enabled for this account');
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not installed');
            return;                            
        }
        
        if($activeSync->status != 'enabled') {
            header('HTTP/1.1 403 ActiveSync not enabled for this account');
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' ActiveSync is not enabled');
            return;                            
        }
        
        // when used with (f)cgi no PHP_AUTH* variables are available without defining a special rewrite rule
        if(!isset($_SERVER['PHP_AUTH_USER'])) {
            // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
            if (isset($_SERVER["REMOTE_USER"])) {
                $basicAuthData = base64_decode(substr($_SERVER["REMOTE_USER"], 6));
            } elseif (isset($_SERVER["REDIRECT_REMOTE_USER"])) {
                $basicAuthData = base64_decode(substr($_SERVER["REDIRECT_REMOTE_USER"], 6));
            } elseif (isset($_SERVER["Authorization"])) {
                $basicAuthData = base64_decode(substr($_SERVER["Authorization"], 6));
            } elseif (isset($_SERVER["HTTP_AUTHORIZATION"])) {
                $basicAuthData = base64_decode(substr($_SERVER["HTTP_AUTHORIZATION"], 6));
            }

            if (isset($basicAuthData) && !empty($basicAuthData)) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(":", $basicAuthData);
            }
        }
        
        if(empty($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        $syncFrontend = new ActiveSync_Frontend_Http();
        
        if($syncFrontend->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) !== true) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        if(Tinebase_Core::getUser()->hasRight('ActiveSync', Tinebase_Acl_Rights::RUN) !== true) {
            header('HTTP/1.1 403 ActiveSync not enabled for account ' . $_SERVER['PHP_AUTH_USER']);
            echo "<b>ERROR</b>!<br>ActiveSync is not enabled for account {$_SERVER['PHP_AUTH_USER']}.";
            return;
        }
        
        switch($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                $syncFrontend->handleOptions();
                break;
                
            case 'POST':
                if(count($_GET) == 1) {
                    $arrayKeys = array_keys($_GET);
                    $parameters = $this->decodeRequestParameters($arrayKeys[0]);
                } else {
                    $parameters = $this->_getRequestParameters();
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST ' . print_r($parameters, true));
                
                $syncFrontend->handlePost($_SERVER['PHP_AUTH_USER'], $parameters['deviceId'], $parameters['deviceType'], $parameters['command'], $parameters['protocolVersion']);
                break;
                
            case 'GET':
                echo "It works!<br>Your username is: {$_SERVER['PHP_AUTH_USER']} and your IP address is: {$_SERVER['REMOTE_ADDR']}.";
                break;
        }
    }
    
    /**
     * return request params
     * 
     * @return array
     */
    protected function _getRequestParameters()
    {
        $result = array(
            'protocolVersion'  => $_SERVER['HTTP_MS_ASPROTOCOLVERSION'],
            'command'          => $_GET['Cmd'],
            'deviceId'         => $_GET['DeviceId'],
            'deviceType'       => $_GET['DeviceType']
        );
        
        return $result;
    }

    /**
     * decode request params
     * 
     * @param array $_requestParameters
     * @return array
     */
    public function decodeRequestParameters($_requestParameters)
    {
        $request = base64_decode($_requestParameters);
        #Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' REQUEST ' . $request);
        
        $stream = fopen('php://temp', 'w');
        fputs($stream, $request);
        rewind($stream);
        
        $result['protocolVersion']  = $this->_readInteger($stream);
        $result['protocolVersion']  = '12.1';
        $result['command']          = $this->_decodeCommandCode($this->_readInteger($stream));
        $result['locale']           = $this->_readShort($stream);
        $result['deviceId']         = $this->_readString($stream);
        $result['policyKey']        = $this->_readString($stream);
        $result['deviceType']       = $this->_readString($stream);
        
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' REQUEST ' . print_r($result, true));

        return $result;
    }
    
    /**
     * decode command
     * 
     * @param string $_code
     */
    protected function _decodeCommandCode($_code)
    {
        $codes = array(
            1   => 'SendMail',
            2   => 'SmartForward',
            3   => 'SmartReply',
            4   => 'GetAttachment',
            5   => 'GetHierarchy',
            6   => 'CreateCollection',
            7   => 'DeleteCollection',
            8   => 'MoveCollection',
            9   => 'FolderSync',
            10  => 'FolderCreate',
            11  => 'FolderDelete',
            12  => 'FolderUpdate',
            13  => 'MoveItems',
            14  => 'GetItemEstimate',
            15  => 'MeetingResponse',
            16  => 'Search',
            17  => 'Settings',
            18  => 'Ping',
            19  => 'ItemOperations',
            20  => 'Provision',
            21  => 'ResolveReceipients',
            22  => 'ValidateCert'
        );
        
        return $codes[$_code];
    }
    
    /**
     * read integer from stream
     * 
     * @param handle $stream
     * @return integer
     */
    protected function _readInteger($stream)
    {
        $byte = fread($stream, 1);
        
        $unpacked = unpack('Cinteger', $byte);

        return $unpacked['integer'];
    }
    
    /**
     * read short from stream
     * 
     * @param handle $stream
     * @return short
     */
    protected function _readShort($stream)
    {
        $bytes = fread($stream, 2);
        
        $unpacked = unpack('nshort', $bytes);

        return $unpacked['short'];
    }
    
    /**
     * read string from stream
     * 
     * @param handle $stream
     * @return string
     */
    protected function _readString($stream)
    {
        $length = $this->_readInteger($stream);
        
        $string = fread($stream, $length);

        return $string;
    }
}
