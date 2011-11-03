<?php
/**
 * contract controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contract controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Contract();
        $this->_modelName = 'Sales_Model_Contract';
        $this->_currentAccount = Tinebase_Core::getUser();   
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Contract
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Contract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller_Contract();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/

    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet
     */
    public function get($_id)
    {
        $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', 'shared');
        return parent::get($_id, $sharedContracts->getId());
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Sales_Model_Contract
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        // add container
        $_record->container_id = self::getSharedContractsContainer()->getId();
        
        // add number
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext(Sales_Model_Number::TYPE_CONTRACT, $this->_currentAccount->getId());
        $_record->number = $number->number;
        
        return parent::create($_record);
    }

    /**
     * get (create if it does not exist) container for shared contracts
     * 
     * @return Tinebase_Model_Container|NULL
     */
    public static function getSharedContractsContainer()
    {
        $sharedContracts = NULL;
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        
        try {
            $sharedContractsId = Tinebase_Config::getInstance()->getConfig(Sales_Model_Config::SHAREDCONTRACTSID, $appId, '')->value;
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => 'Shared Contracts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => $appId,
            ));
            $sharedContracts = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, TRUE);
            
            Tinebase_Config::getInstance()->setConfigForApplication(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId(), 'Sales');
            
            // add grants for groups
            $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
            $adminGroup = $groupsBackend->getDefaultAdminGroup();
            $userGroup  = $groupsBackend->getDefaultGroup();
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT
            ), TRUE);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
                Tinebase_Model_Grants::GRANT_ADD,
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT,
                Tinebase_Model_Grants::GRANT_DELETE,
                Tinebase_Model_Grants::GRANT_ADMIN
            ), TRUE);
        }
        
        return $sharedContracts;
    }
}
