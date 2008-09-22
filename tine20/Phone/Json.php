<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the phone application
 *
 * @package     Phone
 */
class Phone_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Phone';
    
    /**
     * dial number
     *
     * @param int $number phone number
     * @param string $phoneId phone id
     * @param string $lineId phone line id
     * @return array
     */
    public function dialNumber($number, $phoneId, $lineId)
    {
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " $number, $phoneId, $lineId");
        
        $result = array(
            'success'   => TRUE
        );
        
        Phone_Controller::getInstance()->dialNumber($number, $phoneId, $lineId);
        
        return $result;
    }
    
    /**
     * get user phones
     *
     * @return string json encoded array with user phones
     */
    public function getUserPhones($accountId)
    {        
        $voipController = Voipmanager_Controller::getInstance();
        $phones = $voipController->getMyPhones('id', 'ASC', '', $accountId);
        
        // add lines to phones
        $results = array();
        foreach ($phones as $phone) {
            $myPhone = $voipController->getMyPhone($phone->getId(), $accountId);

            $result = $phone->toArray();
            $result['lines'] = $myPhone->lines->toArray();
            $results[] = $result;
        }
        
        $result = array(
            'success'       => TRUE,
            'results'       => $results,
            'totalcount'    => count($phones) 
        );
        
        return $result;        
    }
    
    /**
     * Search for calls matching given arguments
     *
     * @param array $filter
     * @return array
     */
    public function searchCalls($filter)
    {
        $paginationFilter = Zend_Json::decode($filter);
        $filter = new Phone_Model_CallFilter($paginationFilter);
        $pagination = new Tinebase_Model_Pagination($paginationFilter);
        
        $calls = Phone_Controller::getInstance()->searchCalls($filter, $pagination);
        
        //$result = $this->_multipleLeadsToJson($leads);        
        //Zend_Registry::get('logger')->debug(print_r($result,true));
        
        return array(
            'results'       => $calls->toArray(),
            'totalcount'    => Phone_Controller::getInstance()->searchCallsCount($filter)
        );
    }
}
