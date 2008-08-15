<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase Service
 *
 * @todo        clear Post Parameters from test to test
 * @package     Tinebase
 */
class Tinebase_Service extends Tinebase_Service_Abstract
{
    /**
     * @var bool
     */
    public $debugEnabled = false;

    /**
     * login to remote Tine 2.0 installation
     *
     * @return void
     */
    public function login()
    {
        $connection = $this->getConnection();
        
        $connection->setParameterPost(array(
            'username'  => $connection->username,
            'password'  => $connection->password,
            'method'    => 'Tinebase.login'
        ));
        
        $response = $connection->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $connection->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('login failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $connection->jsonKey = $responseData['jsonKey'];
        $connection->user = $responseData['account'];
    }
    
    /**
     * logout from remote Tine 2.0 installation
     * 
     * @return void
     */
    public function logout()
    {
        $connection = $this->getConnection();
        
        $connection->setParameterPost(array(
            'method'   => 'Tinebase.logout'
        ));
        
        $response = $connection->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $connection->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('logout failed');
        }

        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $connection->jsonKey = NULL;
        $connection->user = array();
    }
}