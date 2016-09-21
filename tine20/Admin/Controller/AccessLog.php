<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Access Log Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_AccessLog extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_AccessLog
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Admin';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller_AccessLog
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_AccessLog;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of access log entries
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
        $this->checkRight('VIEW_ACCESS_LOG');
        
        if ($_filter === NULL) {
            $_filter = new Tinebase_Model_Filter_FilterGroup();
        }
        $result = Tinebase_AccessLog::getInstance()->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        
        return $result;
    }
    
    /**
     * returns the total number of access logs
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        return Tinebase_AccessLog::getInstance()->searchCount($_filter, $_action);
    }
    
    /**
     * delete access log entries
     *
     * @param   array $_ids list of logIds to delete
     */
    public function delete($_ids)
    {
        $this->checkRight('MANAGE_ACCESS_LOG');
        
        Tinebase_AccessLog::getInstance()->delete($_ids);
    }    
}
