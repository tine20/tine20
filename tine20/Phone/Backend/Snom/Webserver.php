<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Jan Schneider <edv@janschneider.net>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * snom backend for the Phone application
 * 
 * @package     Phone
 */
class Phone_Backend_Snom_Webserver
{
    /**
     * send command
     *
     * @param string $_phoneAddress Address of the phone
     * @param array  $_params       command params
     */    
    private function sendCommand($_phoneAddress, array $_params = array(), $_user = NULL, $_pass = NULL)
    {
        $_config = array(
            'useragent' => 'PHP snom remote client (rev: 0.1)',
            'keepalive' => TRUE
        );
        
        
        $client = new Zend_Http_Client('http://' . $_phoneAddress . '/command.htm', $_config);
        $client->setAuth($_user, $_pass, Zend_Http_Client::AUTH_BASIC);
        $client->setParameterGet($_params);
        
        $response = $client->request('GET');
        
        if(!$response->isSuccessful()) {
            throw new Phone_Exception_Snom('HTTP request to '. $_phoneAddress .' failed');
        }
        return $response->getBody();
    }
    
    /**
     * initiate new call
     *
     *  http://kb.snom.com/kb/index.php?View=entry&CategoryID=21&EntryID=40
     *
     * @param string $_phoneAddress Address of the phone
     * @param string $_number       Number to dial
     * 
     * @throws  Phone_Exception_Snom
     */
    public function dialNumber($_phoneAddress, $_number, $_user = NULL, $_pass = NULL)
    {
        // @todo move number cleanup elsewhere?
        // @todo remove hardcoded international area code
        $_number = str_replace(' ',   '', $_number);
        $_number = str_replace('-',   '', $_number);
        $_number = str_replace('(0)', '', $_number);
        if (strlen($_number) == 0) {
            throw new Phone_Exception_Snom('No number to dial');
        }
        if (substr($_number, 0, 2) == '00') {
            $_number = '+' . substr($_number, 2);
        }
        else if (substr($_number, 0, 1) == '0') {
            $_number = '+49' . substr($_number, 1);
        }
        $responseBody = $this->sendCommand($_phoneAddress, array('number' => $_number), $_user, $_pass);
    }
    
   /**
     * disconnect call
     * 
     * manager show command hangup
     *
     * @throws  Phone_Exception_Snom
     */
    public function hangup($_phoneAddress, $_user = NULL, $_pass = NULL)
    {
        $responseBody = $this->sendCommand($_phoneAddress, array('key' => 'DISCONNECT'), $_user, $_pass);
    }
}
