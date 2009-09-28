<?php
/**
 * PHP ajam remote client
 *
 * @package     Ajam
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle connection to Asterisk ajam service
 *
 * see http://www.voipinfo.org/wiki/view/Asterisk+manager+API and
 * http://www.voip-info.org/wiki/view/Aynchronous+Javascript+Asterisk+Manager+%28AJAM%29
 * @package     Asterisk
 */
class Ajam_Connection extends Zend_Http_Client
{
    /**
     * enabled / disable debugging
     * 
     * @var boolean
     */
    protected $debug = false;
    
    /**
     * keeps value of baseUri
     * 
     * @var string
     */
    protected $_baseUri;
    
    /**
     * the constructor
     *
     * @param string $_uri the url to connect to
     * @param array $_config see Zend_Http_Client for details
     */    
    public function __construct($_baseUri, array $_config = array())
    {
        $this->_baseUri = $_baseUri;
        
        $_config['useragent'] = 'PHP ajam remote client (rev: 0.2)';
        $_config['keepalive'] = TRUE;
        
        parent::__construct($_baseUri, $_config);
        
        // enable cookie handling
        $this->setCookieJar();
    }
    
    /**
     * login into ajam service
     * 
     * manager show command login
     *
     * @param   string $_username
     * @param   string $_secret
     * @throws  Voipmanager_Exception
     */
    public function login($_username, $_secret)
    {
        $this->resetParameters();

        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'    => 'login',
            'username'  => $_username,
            'secret'    => $_secret
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }
    }
    
    
    /**
     * disconnect call
     * 
     * manager show command hangup
     *
     * @param   string $_channel The channel name to be hungup
     * @throws  Ajam_Exception
     */
    public function hangup($_channel)
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'    => 'hangup',
            'channel'  	=> $_channel
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * redirect call
     * 
     * manager show command redirect
     *
     * @param   string $_channel which channel to redirect
     * @param   string $_exten where to redirect
     * @throws  Ajam_Exception
     */
    public function redirect($_channel, $_exten)
    {
        $this->resetParameters();

        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'    => 'redirect',
            'priority'	=> 1,
            'channel'  	=> $_channel,
            'exten'	=> $_exten
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response );
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * get status of specific or all channels
     *
     * manager show command status
     * 
     * @param   string $_channel NULL for all channels as string to filter channels by name
     * @return  array objects of matching channels
     * @throws  Ajam_Exception
     */
    public function status($_channel)
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'    => 'status',
            'channel'   => $_channel
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response[0]->generic['message']);
        }
        
        $result = array();
        
        foreach($xml->response as $statusRow) {
          if($statusRow->generic['event'] == 'Status' and ($_channel === NULL or stripos($statusRow->generic['channel'], $_channel) === 0) )  {
            $status = new stdClass;
            foreach($statusRow->generic->attributes() as $key => $value) {
              $status->$key = (string)$value;
            }
            $result[] = $status;
          }
        }
        
        return $result;
    }
    
    /**
     * disconnect from ajam service
     * 
     * manager show command logout
     * 
     * @throws  Ajam_Exception
     */
    public function logout()
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'   => 'logoff'
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Goodbye') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }
    }

    /**
     * list all sippeers
     *
     * manager show command sippeers
     * 
     * @return array objects of listpeers
     * @throws  Ajam_Exception
     */
    public function sippeers()
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'   => 'sippeers'
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }

        $result = array();
        foreach($xml->response as $statusRow) {
          if($statusRow->generic['event'] == 'PeerEntry')  {
            $status = new stdClass;
            foreach($statusRow->generic->attributes() as $key => $value) {
              $status->$key = (string)$value;
            }
            $result[] = $status;
          }
        }
        
        return $result;
    }

    /**
     * initiate new call
     *
     * manager show command originate
     * 
     * @param string $_channel Channel name to call
     * @param string $_context Context to use
     * @param string $_exten Extension to use
     * @param int $_priority Priority to use
     * @param string $_callerId Caller ID to be set on the outgoing channel
     * 
     * @throws  Ajam_Exception
     */
    public function originate($_channel, $_context, $_exten, $_priority, $_callerId="Ajam Service")
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'    => 'originate',
            'channel'	=> $_channel,
            'context'	=> $_context,
            'exten'	    => $_exten,
            'priority'	=> $_priority,
            'callerid'	=> $_callerId,
            'async'	    => 1
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
        
        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }        
    }
    
    /**
     * execute asterisk cli command
     *
     * manager show command Command
     * 
     * @param string $_command Asterisk CLI command to run
     * @throws Ajam_Exception
     */
    public function command($_command)
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'   => 'command',
            'command'	=> $_command
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }

        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success' && $xml->response->generic['response'] != 'Follows') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }        
    }
    
    /**
     * Retrieve configuration file in JSON format
     *
     * manager show command GetConfigJSON
     * 
     * @param string $_filename
     * @throws Ajam_Exception
     */
    public function getConfigJson($_filename)
    {
        $this->resetParameters();
        
        $this->setUri($this->_baseUri . '/mxml');
        
        $this->setParameterGet(array(
            'action'   => 'GetConfigJSON',
            'Filename'	=> $_filename
        ));
        
        $response = $this->request('GET');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }

        $xml = new SimpleXMLElement($response->getBody());
        
        if($this->debug === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Ajam_Exception($xml->response->generic['message']);
        }        
    }

    /**
     * upload file to asterisk server
     *
     * [post_mappings]
     * config = /etc/asterisk/config
     * 
     * @param string $_uri
     * @param string $_filename
     * @param string $content
     * @throws Ajam_Exception
     */
    public function upload($_uri, $_filename, $_content = null)
    {
        $this->resetParameters();
        
        $this->setUri($_uri);
        
        $this->setFileUpload($_filename, 'upload', $_content);
        
        $response = $this->request('POST');
        
        if($this->debug === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Ajam_Exception('HTTP request failed');
        }
    }
    
    /**
     * enable debug output
     *
     * @param bool $_status
     */
    public function setDebug($_status)
    {
        $this->debug = (bool)$_status;
    }
}