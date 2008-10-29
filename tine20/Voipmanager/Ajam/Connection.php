<?php
/**
 * Tine 2.0
 *
 * @package     Asterisk
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class to handle connection to ajam service
 *
 * see http://www.voipinfo.org/wiki/view/Asterisk+manager+API and
 * http://www.voip-info.org/wiki/view/Aynchronous+Javascript+Asterisk+Manager+%28AJAM%29
 * @package     Asterisk
 */
class Voipmanager_Ajam_Connection extends Voipmanager_Ajam_Http_Client
{
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $debugEnabled = false;
    
    /**
     * the constructor
     *
     * @param string $_uri the url to connect to
     * @param array $_config see Zend_Http_Client for details
     */    
    public function __construct($_uri, array $_config = array())
    {
        $_config['useragent'] = 'Ajam remote client (rv: 0.1)';
        $_config['keepalive'] = TRUE;
        
        parent::__construct($_uri, $_config);
        
        $this->setCookieJar();
    }
    
    /**
     * login into ajam service
     *
     * @param   string $_username
     * @param   string $_secret
     * @throws  Voipmanager_Exception
     */
    public function login($_username, $_secret)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'login',
            'username'  => $_username,
            'secret'    => $_secret
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump($this->getLastRequest());
            var_dump($response);
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Voipmanager_Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * disconnect call
     * 
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Hangup
     *
     * @param   unknown_type $_channel
     * @throws  Voipmanager_Exception
     */
    public function hangup($_channel)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'hangup',
            'channel'  	=> $_channel
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Voipmanager_Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * redirect call
     * 
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Redirect
     *
     * @param   string $_channel which channel to redirect
     * @param   string $_exten where to redirect
     * @throws  Voipmanager_Exception
     */
    public function redirect($_channel, $_exten)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'redirect',
            'priority'	=> 1,
            'channel'  	=> $_channel,
            'exten'	=> $_exten
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Voipmanager_Exception($xml->response->generic['message']);
        }
    }
    
    /**
     * get status of specific or all channels
     *
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Status
     * 
     * @param   string $_channel NULL for all channels as string to filter channels by name
     * @return  array objects of matching channels
     * @throws  Voipmanager_Exception
     */
    public function status($_channel = NULL)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'    => 'status'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Voipmanager_Exception($xml->response[0]->generic['message']);
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
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Logoff
     * @throws  Voipmanager_Exception
     */
    public function logout()
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'logoff'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Goodbye') {
            throw new Voipmanager_Exception($xml->response->generic['message']);
        }
    }

    /**
     * list all sippeers
     *
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+SIPpeers
     * 
     * @return array objects of listpeers
     * @throws  Voipmanager_Exception
     */
    public function sippeers()
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'sippeers'
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('HTTP request failed');
        }
                
        $xml = new SimpleXMLElement( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($xml->response->generic);
        }
        
        if($xml->response->generic['response'] != 'Success') {
            throw new Voipmanager_Exception($xml->response->generic['message']);
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
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Originate
     * 
     * @param string $_channel
     * @param string $_context
     * @param string $_exten
     * @param int $_priority
     * @param string $_callerId
     * @throws  Voipmanager_Exception
     */
    public function originate($_channel, $_context, $_exten, $_priority, $_callerId="Ajam Service")
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'originate',
            'channel'	=> $_channel,
            'context'	=> $_context,
            'exten'	=> $_exten,
            'priority'	=> $_priority,
            'callerid'	=> $_callerId,
            'async'	=> 1
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('logout failed');
        }

        $dom = new DomDocument();
        $dom->loadXML( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($dom);
        }
    }
    
    /**
     * execute asterisk cli command
     *
     * http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Command
     * 
     * @param string $_command
     * @throws  Voipmanager_Exception
     */
    public function command($_command)
    {
        $this->resetParameters();
        $this->setParameterGet(array(
            'action'   => 'command',
            'command'	=> $_command
        ));
        
        $response = $this->request('GET');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Voipmanager_Exception('logout failed');
        }

        $dom = new DomDocument();
        $dom->loadXML( $response->getBody() );
        
        if($this->debugEnabled === true) {
            var_dump($dom);
        }
    }
    
    /**
     * enable debug output
     *
     * @param bool $_status
     */
    public function setDebugEnabled($_status)
    {
        $this->debugEnabled = (bool)$_status;
    }
}