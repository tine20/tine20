<?php
/**
 * contract controller for Erp application
 * 
 * @package     Erp
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * contract controller class for Erp application
 * 
 * @package     Erp
 * @subpackage  Controller
 */
class Erp_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Erp';
        $this->_backend = new Erp_Backend_Contract();
        $this->_modelName = 'Erp_Model_Contract';
        $this->_currentAccount = Tinebase_Core::getUser();   
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Erp_Controller_Contract
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Erp_Controller_Contract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Erp_Controller_Contract();
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
        $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Contracts', 'shared');
        return parent::get($_id, $sharedContracts->getId());
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Erp_Model_Contract
     */
    public function create(Tinebase_Record_Interface $_record)
    {        
        // add container
        $_record->container_id = Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Contracts', 'shared')->getId();
        
        // add number
        $numberBackend = new Erp_Backend_Number();
        $number = $numberBackend->getNext(Erp_Model_Number::TYPE_CONTRACT, $this->_currentAccount->getId());
        $_record->number = $number->number;
        
        return parent::create($_record);
    }

}
