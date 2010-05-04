<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add account id to sql statements to check acl
 * 
 */


/**
 * persistent filter controller
 */
class Tinebase_PersistentFilter extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     * 
     * @var boolean
     */
    protected $_doRightChecks = FALSE;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * ommit mod log for this records
     * 
     * @var boolean
     */
    protected $_ommitModLog = TRUE;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_PersistentFilter';
    
    /**
     * @var Tinebase_PersistentFilter
     */
    private static $_instance = NULL;
    
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend         = new Tinebase_PersistentFilter_Backend_Sql();
        $this->_currentAccount  = Tinebase_Core::getUser();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Tinebase_PersistentFilter
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_PersistentFilter();
        }
        return self::$_instance;
    }
    
    /**
     * returns persistent filter identified by id
     * 
     * @param  string $_id
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function getFilterById($_id)
    {
        $persistentFilter = self::getInstance()->get($_id);
        
        return $persistentFilter->filters;
    }
    
    
}
