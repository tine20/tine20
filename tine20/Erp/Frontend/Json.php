<?php
/**
 * Tine 2.0
 * @package     Erp
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 *
 * This class handles all Json requests for the Erp application
 *
 * @package     Erp
 * @subpackage  Frontend
 */
class Erp_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Erp';
    }
    
    /**
     * Return a single Contract
     *
     * @param string $uid
     * @return array contract data
     */
    public function getContract($uid)
    {
        $contract = Erp_Controller_Contract::getInstance()->get($uid);
        return $this->_recordToJson($contract);
    }
    
    
    /**
     * creates/updates a Contract
     *
     * @param  $recordData
     * @return array created/updated Contract
     */
    public function saveContract($recordData)
    {
        $controller = Erp_Controller_Contract::getInstance();
        $record = $this->_save($recordData, $controller, 'Erp_Model_Contract');
        
        return $this->_recordToJson($record);
    }
    
    
    /**
     * Deletes existing Contracts
     *
     * @param array $ids 
     * @return string
     */
    public function deleteContracts($ids)
    {
        if (strlen($ids) > 40) {
            $ids = Zend_Json::decode($ids);
        }
        Erp_Controller_Contract::getInstance()->delete($ids);
        return 'success';
    }
}
