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
        
        $result = $results;
        
        return $result;        
    }
    
    /**
     * Search for calls matching given arguments
     *
     * @param array $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchCalls($filter, $paging)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(Zend_Json::decode($filter), true));
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r(Zend_Json::decode($paging), true));
        
        $filter = new Phone_Model_CallFilter(Zend_Json::decode($filter));
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        $calls = Phone_Controller::getInstance()->searchCalls($filter, $pagination);
        
        // set timezone
        $calls->setTimezone(Zend_Registry::get('userTimeZone'));
                
        return array(
            'results'       => $calls->toArray(),
            'totalcount'    => Phone_Controller::getInstance()->searchCallsCount($filter)
        );
    }
    
    /**
     * save one myPhone
     *
     * if $phoneData['id'] is empty the phone gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of phone properties
     * @return array
     */
    public function saveMyPhone($phoneData)
    {
        $phoneData = Zend_Json::decode($phoneData);
        $voipController = Voipmanager_Controller::getInstance();
        
        // unset if empty
        if (empty($phoneData['id'])) {
            unset($phoneData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        $phone = new Voipmanager_Model_MyPhone();
        $phone->setFromArray($phoneData);
        
        $phoneSettings = new Voipmanager_Model_SnomPhoneSettings();
        $phoneSettings->setFromArray($phoneData);

        $currentAccount = Zend_Registry::get('currentAccount')->toArray();
        
        if (!empty($phone->id)) {
            $phone = $voipController->updateMyPhone($phone, $phoneSettings, $currentAccount['accountId']);
        } 
        
        $phone = $voipController->getSnomPhone($phone->getId())->toArray();

        $result = array('success'           => true,
            'welcomeMessage'    => 'Entry updated',
            'updatedData'       => $phone
        );
        
        return $result;         
    }
    
    /**
     * Returns registry data of the phone application.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {   
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__);
    
        $accountId = Zend_Registry::get('currentAccount')->getId();
        
        $registryData = array(
            'Phones' => $this->getUserPhones($accountId)
        );
        
        return $registryData;
    }
}
