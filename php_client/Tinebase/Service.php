<?php
/**
 * Tine 2.0 PHP HTTP Client
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
 * @package     Tinebase
 */
class Tinebase_Service extends Tinebase_Service_Abstract
{
    /**
     * @var bool
     */
    public $debugEnabled = false;
    
    /**
     * returns all containers of given type, given owner, current user has access to
     *
     * @param  string $_application
     * @param  string $_containerType
     * @param  int    $_owner
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Container
     */
    public function getContainer($_application, $_containerType, $_owner)
    {
        $response = $this->_connection->request(array(
            'method'        => 'Tinebase_Container.getContainer',
            'application'   => $_application,
            'containerType' => $_containerType,
            'owner'         => $_owner,
        ));
        
        if(!$response->isSuccessful()) {
            throw new Exception('getting containers failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $containers = new Tinebase_Record_RecordSet('Tinebase_Model_Container', $responseData);
        return $containers;
    }
    
    /**
     * returns tags for given context (application) current user has given rights to
     *
     * @param string $_context
     * @param string $_right
     * @return Tinebase_Record_RecordSet of Tinbebase_Model_Tag
     */
    public function getTags($_context, $_right) {
        
        $this->_connection->request(array(
            'method'    => 'Tinebase.getTags',
            'context'   => $_context,
        ));        
        $response = $client->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $client->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('getting contact failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
       
        return $responseData;
    
    }
}