<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Access Log Controller for Admin application
 *
 * @package     Admin
 */
class Admin_Controller_AccessLog extends Tinebase_Application_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Admin_Controller_AccessLog
     */
    private static $_instance = NULL;
    
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
     * @param string $_filter string to search accounts for
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_AccessLog set of matching access log entries
     */
    public function getAccessLogEntries($_filter = NULL, $_pagination = NULL, $_from = NULL, $_to = NULL)
    {
        $this->checkRight('VIEW_ACCESS_LOG');        
        
        $tineAccessLog = Tinebase_AccessLog::getInstance();

        $result = $tineAccessLog->getEntries($_filter, $_pagination, $_from, $_to);
        
        return $result;
    }

    /**
     * returns the total number of access logs
     * 
     * @param Zend_Date $_from the date from which to fetch the access log entries from
     * @param Zend_Date $_to the date to which to fetch the access log entries to
     * @param string $_filter OPTIONAL search parameter
     * 
     * @return int
     */
    public function getTotalAccessLogEntryCount($_from, $_to, $_filter)
    {
        return Tinebase_AccessLog::getInstance()->getTotalCount($_from, $_to, $_filter);
    }
    
    /**
     * delete access log entries
     *
     * @param   array $_logIds list of logIds to delete
     */
    public function deleteAccessLogEntries($_logIds)
    {
        $this->checkRight('MANAGE_ACCESS_LOG');        
        
        Tinebase_AccessLog::getInstance()->deleteEntries($_logIds);
    }    
}
