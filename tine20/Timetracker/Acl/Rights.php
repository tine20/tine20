<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * this class handles the rights for the Timetracker application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * @package     Timetracker
 * @subpackage  Acl
 */
class Timetracker_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage timeaccounts
     * @staticvar string
     */
    const MANAGE_TIMEACCOUNTS = 'manage_timeaccounts';
        
    /**
     * the right to add timeaccounts
     * @staticvar string
     */
    const ADD_TIMEACCOUNTS = 'add_timeaccounts';
        
    /**
     * the right to manage shared timeaccount favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_TIMEACCOUNT_FAVORITES = 'manage_shared_timeaccount_favorites';
    
    /**
     * the right to manage shared timesheet favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_TIMESHEET_FAVORITES = 'manage_shared_timesheet_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Timetracker_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        
    }    
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timetracker_Acl_Rights;
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        $allRights = parent::getAllApplicationRights();
        
        $addRights = array ( 
            self::MANAGE_TIMEACCOUNTS,
            self::ADD_TIMEACCOUNTS,
            self::MANAGE_SHARED_TIMEACCOUNT_FAVORITES,
            self::MANAGE_SHARED_TIMESHEET_FAVORITES,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Timetracker');
        
        $rightDescriptions = array(
            self::MANAGE_TIMEACCOUNTS  => array(
                'text'          => $translate->_('Manage timeaccounts'),
                'description'   => $translate->_('Add, edit and delete timeaccounts (includes all timesheet grants)'),
            ),
            self::ADD_TIMEACCOUNTS  => array(
                'text'          => $translate->_('Add timeaccounts'),
                'description'   => $translate->_('Add timeaccounts'),
            ),
            self::MANAGE_SHARED_TIMEACCOUNT_FAVORITES => array(
                'text'          => $translate->_('Manage shared timeaccount favorites'),
                'description'   => $translate->_('Create or update shared timeaccount favorites'),
            ),
            self::MANAGE_SHARED_TIMESHEET_FAVORITES => array(
                'text'          => $translate->_('Manage shared timesheet favorites'),
                'description'   => $translate->_('Create or update shared timesheet favorites'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

    
}
