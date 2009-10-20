<?php
/**
 * contract controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        $_record->container_id = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', 'shared')->getId();
        
        // add number
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext(Sales_Model_Number::TYPE_CONTRACT, $this->_currentAccount->getId());
        $_record->number = $number->number;
        
        return parent::create($_record);
    }

}
