<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * this class handles the rights for the sipgate application
 * @package     Tinebase
 * @subpackage  Acl
 */
class Sipgate_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
   /**
     * the right to manage accounts
     * @static string
     */
    const MANAGE_ACCOUNTS        = 'manage_accounts';            // this enables the account module
    const MANAGE_SHARED_ACCOUNTS = 'manage_shared_accounts';     // user is allowed to manage shared accounts
    const MANAGE_PRIVATE_ACCOUNTS = 'manage_private_accounts';     // user is allowed to manage private accounts
    
    const SYNC_LINES = 'sync_lines';   // user is allowed to sync his lines
    
        
    /**
     * holds the instance of the singleton
     *
     * @var Sipgate_Acl_Rights
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
     * @return Sipgate_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Acl_Rights;
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
        return array_merge(parent::getAllApplicationRights(), array(
            self::MANAGE_SHARED_ACCOUNTS,
            self::MANAGE_PRIVATE_ACCOUNTS,
            self::MANAGE_ACCOUNTS,
            self::SYNC_LINES
        ));
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Sipgate');
        
        $rightDescriptions = array(
            self::MANAGE_ACCOUNTS  => array(
                'text'          => $translate->_('manage accounts'),
                'description'   => $translate->_('enables the account module in the application'),
            ),
            self::SYNC_LINES => array(
                'text'          => $translate->_('sync lines'),
                'description'   => $translate->_('allows the user to sync the call history'),
                ),
            self::MANAGE_SHARED_ACCOUNTS  => array(
                'text'          => $translate->_('manage shared accounts'),
                'description'   => $translate->_('add, edit and delete shared accounts'),
            ),
            self::MANAGE_PRIVATE_ACCOUNTS  => array(
                'text'          => $translate->_('manage private accounts'),
                'description'   => $translate->_('add, edit and delete private accounts'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

    
}
