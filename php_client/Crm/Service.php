<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     Crm
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Crm Service
 *
 * @package     Crm
 */
class Crm_Service extends Tinebase_Service_Abstract
{
    /**
     * adds / creates a new lead in the remote installation
     *
     * @param  Crm_Model_Lead $_lead
     * @return Crm_Model_Lead
     */
    public function addLead(Crm_Model_Lead $_lead)
    {
        if(!$_lead->isValid()) {
            throw new Exception('lead is not valid');
        }
        
        $response = $this->_connection->request(array(
            'method' => 'Crm.saveLead',
            'lead'   => Zend_Json::encode($_lead->toArray())
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('adding lead failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $lead = new Crm_Model_Lead($responseData);
        return $lead;
    }
    
    /**
     * retreaves a remote lead identified by its id
     *
     * @param  int $_leadId
     * @return Crm_Model_Lead
     */
    public function getLead($_leadId)
    {
        $response = $this->_connection->request(array(
            'method'    => 'Crm.getLead',
            'leadId' => $_leadId
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('getting lead failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $lead = new Crm_Model_Lead($responseData);
        return $lead;
    }
    
    /**
     * deletes a remote lead identified by its id
     *
     * @param  int $_id
     * @return void
     */
    public function deleteLead($_id)
    {
        $response = $this->_connection->request(array(
            'method'   => 'Crm.deleteLeads',
            '_leadIds'  => Zend_Json::encode(array($_id))
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('deleting lead failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
}
