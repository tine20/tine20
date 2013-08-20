<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * All full configured models
     * 
     * @var array
     */
    protected $_configuredModels = array('Call', 'MyPhone');
    protected $_defaultModel = 'Call';
    /**
     * app name
     * 
     * @var string
     */
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Dialing number $number with $phoneId (line $lineId)");
        
        Phone_Controller::getInstance()->dialNumber($number, $phoneId, $lineId);
        
        return array(
            'success'   => TRUE
        );
    }
    
    /**
     * Search for calls matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchMyPhones($filter, $paging)
    {
        return $this->_search($filter, $paging, Phone_Controller_MyPhone::getInstance(), 'Phone_Model_MyPhoneFilter');
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
        return $this->_search($filter, $paging, Phone_Controller_Call::getInstance(), 'Phone_Model_CallFilter');
    }
    
    /**
     * get one phone identified by phoneId
     *
     * @param  int $id
     * @return array
     */
    public function getMyPhone($id)
    {
        return $this->_get($id, Phone_Controller_MyPhone::getInstance());
    } 
    
    /**
     * save user phone
     *
     * @param array $recordData an array of phone properties
     * @return array
     */
    public function saveMyPhone($recordData)
    {
        unset($recordData['template_id']);

        $phone = new Phone_Model_MyPhone();
        $phone->setFromArray($recordData);
        
        $phoneSettings = new Voipmanager_Model_Snom_PhoneSettings();
        $phoneSettings->setFromArray($recordData);
        $phone->settings = $phoneSettings;
        
        $phone->lines = new Tinebase_Record_RecordSet(
            'Voipmanager_Model_Snom_Line', 
            (isset($recordData['lines']) && !empty($recordData['lines'])) ? $recordData['lines'] : array(),
            TRUE
        );
        
        if (! empty($phone->id)) {
            $phone = Phone_Controller_MyPhone::getInstance()->update($phone);
        } else {
            // will throw exception in any case
            $phone = Phone_Controller_MyPhone::getInstance()->create($phone);
        }
        
        return $this->getMyPhone($phone->getId());
    } 
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $recordArray = parent::_recordToJson($_record);
        
        switch (get_class($_record)) {
            case 'Phone_Model_MyPhone':
                // add settings
                $settings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($_record->getId());
                $recordArray = array_merge($recordArray, $settings->toArray());
                
                // resolve lines
                foreach ($recordArray['lines'] as &$line) {
                    $line['asteriskline_id'] = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($line['asteriskline_id'])->toArray();
                }
                
                break;
        }
        
        return $recordArray;
    }
    
    /**
     * Returns registry data of the phone application.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        // get user phones
        $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        ));
        $phones = Phone_Controller_MyPhone::getInstance()->search($filter);
        foreach ($phones as $phone) {
            $filter = new Voipmanager_Model_Snom_LineFilter(array(
                array('field' => 'snomphone_id', 'operator' => 'equals', 'value' => $phone->id)
            ));
            $phone->lines  = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);
        }
        
        $registryData = array(
            'Phones' => $phones->toArray()
        );
        
        return $registryData;
    }
}
