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
     * delete multiple contacts
     *
     * @param array $_contactIDs list of contactId's to delete
     * @return array
     */
    public function dialNumber($number)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Phone_Controller::getInstance()->dialNumber($number);
        
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

        echo Zend_Json::encode($phones->toArray());        
        die;
    }
}