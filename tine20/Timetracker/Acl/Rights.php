<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
            Tinebase_Acl_Rights::MANAGE_SHARED_FAVORITES,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    private function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Timetracker');
        
        $rightDescriptions = array(
            self::MANAGE_TIMEACCOUNTS  => array(
                'text'          => $translate->_('Manage timeaccounts'),
                'description'   => $translate->_('Add, edit and delete timeaccounts'),
            ),
            Tinebase_Acl_Rights::MANAGE_SHARED_FAVORITES => array(
                'text'          => $translate->_('manage shared timetracker favorites'),
                'description'   => $translate->_('Create or update shared timetracker favorites'),
            ),
        );
        
        return $rightDescriptions;
    }

    /**
     * get right description
     * 
     * @param   string right
     * @return  array with text + description
     */
    public function getRightDescription($_right)
    {        
        $result = parent::getRightDescription($_right);
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
    
}
