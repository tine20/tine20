<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class TineClient_Connection extends Zend_Http_Client
{
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $debugEnabled = false;
    
    public function __construct($_uri, $_config, $_headers)
    {
        parent::__construct($_uri, $_config, $_headers);
        
        $this->setCookieJar();
        $this->setHeaders('X-Requested-With', 'XMLHttpRequest');
    }
    
    public function login($_username, $_password)
    {
        $this->setParameterPost(array(
            'username'  => $_POST['username'],
            'password'  => $_POST['password'],
            'method'    => 'Tinebase.login'
        ));
        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('login failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
    
    public function logout()
    {
        $this->setParameterPost(array(
            'method'   => 'Tinebase.logout'
        ));
        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('logout failed');
        }

        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
    
    public function addContact($_contactData)
    {
        $this->setParameterPost(array(
            'method'   => 'Addressbook.saveContact',
            'contactData'  => Zend_Json::encode($_contactData)
        ));        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('adding contact failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
    
    public function setDebugEnabled($_status)
    {
        $this->debugEnabled = (bool)$_status;
    }
}