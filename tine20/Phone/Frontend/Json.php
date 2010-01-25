<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the phone application
 *
 * @package     Phone
 */
class Phone_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Phone';
    
    /**
     * dial number
     *
     * @param  int    $number  phone number
     * @param  string $phoneId phone id
     * @param  string $lineId  phone line id
     * @return array
     */
    public function dialNumber($number, $phoneId, $lineId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Dialing number $number with $phoneId (line $lineId)");
        
        Phone_Controller::getInstance()->dialNumber($number, $phoneId, $lineId);
        
        return array(
            'success'   => TRUE
        );
    }
    
    /**
     * get user phones
     *
     * @return array array with user phones
     * @todo add account id filter again
     */
    public function getUserPhones($accountId)
    {        
        $voipController = Voipmanager_Controller_MyPhone::getInstance();
        
        $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        ));
        $phones = $voipController->search($filter);
        
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
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCalls($filter, $paging)
    {
        $filter = new Phone_Model_CallFilter($filter);
        $pagination = new Tinebase_Model_Pagination($paging);
        
        $calls = Phone_Controller::getInstance()->searchCalls($filter, $pagination);
        
        // set timezone
        $calls->setTimezone(Tinebase_Core::get('userTimeZone'));
                
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
     * @param  array $phoneData an array of phone properties
     * @return array
     */
    public function saveMyPhone($phoneData)
    {
        $voipController = Voipmanager_Controller_MyPhone::getInstance();
        
        // unset if empty
        if (empty($phoneData['id'])) {
            unset($phoneData['id']);
        }
        
        $phone = new Voipmanager_Model_MyPhone();
        $phone->setFromArray($phoneData);
        
        $phoneSettings = new Voipmanager_Model_Snom_PhoneSettings();
        $phoneSettings->setFromArray($phoneData);
        $phone->settings = $phoneSettings;
        
        if (!empty($phone->id)) {
            $phone = $voipController->update($phone);
        } 
        
        $result = array('success'           => true,
            'welcomeMessage'    => 'Entry updated',
            'updatedData'       => $phone->toArray()
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
        $accountId = Tinebase_Core::getUser()->getId();
        
        try {
            $phones = $this->getUserPhones($accountId);
        } catch (Voipmanager_Exception_AccessDenied $vead) {
            $phones = array();
        }
        
        $registryData = array(
            'Phones' => $phones
        );
        
        return $registryData;
    }
}
