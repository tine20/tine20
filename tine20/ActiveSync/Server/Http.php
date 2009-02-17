<?php
class ActiveSync_Server_Http extends Tinebase_Server_Abstract 
{
    /**
     * handler for ActiveSync requests
     * 
     * @return boolean
     */
    public function handle()
    {
        $this->_initFramework();
        
        if(!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is ActiveSync request.');

        $syncFrontend = new ActiveSync_Frontend_Http();
        
        switch($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                $syncFrontend->handleOptions();
                break;
                
            case 'POST':
                if($syncFrontend->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) !== true) {
                    header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
                    header('HTTP/1.1 401 Unauthorized');
                    return;                            
                }
                #if(Tinebase_Core::getUser()->hasRight('ActiveSync', Tinebase_Acl_Rights::RUN) !== true) {
                #    header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
                #    header('HTTP/1.1 403 ActiveSync not enabled for account');
                #    return;                            
                #}
                $syncFrontend->handlePost($_GET['User'], $_GET['DeviceId'], $_GET['DeviceType'], $_GET['Cmd']);
                break;
                
            case 'GET':
                if($syncFrontend->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) !== true) {
                    header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
                    header('HTTP/1.1 401 Unauthorized');
                    return;                            
                }
                if(Tinebase_Core::getUser()->hasRight('ActiveSync', Tinebase_Acl_Rights::RUN) !== true) {
                    header('WWW-Authenticate: Basic realm="ActiveSync for Tine 2.0"');
                    header('HTTP/1.1 403 ActiveSync not enabled for account');
                    echo "<b>ERROR</b>!<br>ActiveSync is not enabled for account {$_SERVER['PHP_AUTH_USER']}.";
                    return;                            
                }
                echo "It works!<br>Your username is: {$_SERVER['PHP_AUTH_USER']} and your IP address is: {$_SERVER['REMOTE_ADDR']}.";
                break;
        }
    }    
}