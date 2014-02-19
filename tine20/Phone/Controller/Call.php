<?php
/**
 * Call controller for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Call controller class for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_Call extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Phone';
        $this->_backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        $this->_modelName = 'Phone_Model_Call';
        $this->_purgeRecords = TRUE;
        $this->_doRightChecks = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_Call
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_Call
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $_filter->setId('OuterFilter');
    
        return parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
    }
// TODO: make this work and remove setting totalcount in fe
//     /**
//      * Gets total count of search with $_filter
//      *
//      * @param Tinebase_Model_Filter_FilterGroup $_filter
//      * @param string $_action for right/acl check
//      * @return int
//      */
//     public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
//     {
//         $_filter->setId('OuterFilter');
        
//         return count($this->search($_filter, NULL, FALSE, TRUE));
//     }
    
    /**
     * Gets the phone id filter for the first record in the given recordset
     *
     * @param Tinebase_Record_RecordSet $userPhones
     * @return Tinebase_Model_Filter_Abstract
     */
    protected function _getDefaultPhoneFilter($userPhones)
    {
        if ($userPhones->count()) {
            $filter = new Phone_Model_CallFilter(
                array('id' => 'defaultAdded', 'field' => 'phone_id', 'operator' => 'AND', 'value' => array(
                    array('field' => ':id', 'operator' => 'in', 'value' => $userPhones->getId())
                ))
            );
        } else {
            $filter = new Tinebase_Model_Filter_Text(array('id' => 'defaultAdded', 'field' => 'id', 'operator' => 'equals', 'value' => 'notexists'));
        }
    
        return $filter;
    }
}
