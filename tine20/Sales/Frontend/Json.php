<?php
/**
 * Tine 2.0
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add functions again (__call interceptor doesn't work because of the reflection api)
 * @todo        check if we can add these functions to the reflection without implementing them here
 */

/**
 *
 * This class handles all Json requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{   
    /**
     * contract controller
     *
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Sales';
        $this->_contractController = Sales_Controller_Contract::getInstance();
    }
    
   /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $sharedContainer = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', 'shared')->toArray();
        $sharedContainer['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $sharedContainer['id'])->toArray();
        
        return array(
            'DefaultContainer' => $sharedContainer
        );
    }
    
    /*************************** contracts functions *****************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_contractController, 'Sales_Model_ContractFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContract($id)
    {
        return $this->_get($id, $this->_contractController);
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveContract($recordData)
    {
        return $this->_save($recordData, $this->_contractController, 'Contract');
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteContracts($ids)
    {
        $this->_delete($ids, $this->_contractController);
    }
}
