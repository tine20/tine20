<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * Application Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_Application extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_Application
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
     * @return Admin_Controller_Application
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_Application;
        }
        
        return self::$_instance;
    }
    
    /**
     * get list of applications
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_RecordSet_Application
     */
    public function search($filter, $sort, $dir, $start, $limit)
    {
        $this->checkRight('VIEW_APPS');
        
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplications($filter, $sort, $dir, $start, $limit);
    }    

    /**
     * get application
     *
     * @param   int $_applicationId application id to get
     * @return  Tinebase_Model_Application
     */
    public function get($_applicationId)
    {
        $this->checkRight('VIEW_APPS');
        
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getApplicationById($_applicationId);
    }
    
    /**
     * returns the total number of applications installed
     * 
     * @param string $_filter
     * @return int
     */
    public function getTotalApplicationCount($_filter)
    {
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->getTotalApplicationCount($_filter);
    }
    
    /**
     * set application state
     *
     * @param   array $_applicationIds  array of application ids
     * @param   string $_state           state to set
     */
    public function setApplicationState($_applicationIds, $_state)
    {
        $this->checkRight('MANAGE_APPS');
        
        $tineApplications = Tinebase_Application::getInstance();
        
        return $tineApplications->setApplicationState($_applicationIds, $_state);
    }           
}
