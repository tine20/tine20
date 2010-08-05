<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        move some functionality to Tinebase_Acl_Roles
 * @todo        use the defined ACCOUNT_TYPE consts anywhere
 */

/**
 * this class handles the rights for a given application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * NOTE: This is a hibrite class. On the one hand it serves as the general
 *       Rights class to retreave rights for all apss for.
 *       On the other hand it also handles the Tinebase specific rights.
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to send bugreports
     * @staticvar string
     */
    const REPORT_BUGS = 'report_bugs';
    
    /**
     * the right to check for new versions
     * @staticvar string
     */
    const CHECK_VERSION = 'check_version';
    
    /**
     * the right to manage the own profile
     * @staticvar string
     */
    const MANAGE_OWN_PROFILE = 'manage_own_profile';
    
    /**
     * account type anyone
     * @staticvar string
     */
    const ACCOUNT_TYPE_ANYONE   = 'anyone';
    
    /**
     * account type user
     * @staticvar string
     */
    const ACCOUNT_TYPE_USER     = 'user';

    /**
     * account type group
     * @staticvar string
     */
    const ACCOUNT_TYPE_GROUP    = 'group';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Acl_Rights
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
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Acl_Rights;
        }
        
        return self::$_instance;
    }        
            
    /**
     * get all possible application rights
     *
     * @param   string  $_application application name
     * @return  array   all application rights
     */
    public function getAllApplicationRights($_application = NULL)
    {        
        $allRights = parent::getAllApplicationRights();
                
        if ( $_application === NULL || $_application === 'Tinebase' ) {
            $addRights = array(
                self::REPORT_BUGS,
                self::CHECK_VERSION,
                self::MANAGE_OWN_PROFILE,
            );
        } else {
            $addRights = array();
        }
        
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
        $translate = Tinebase_Translation::getTranslation('Tinebase');

        $rightDescriptions = array(            
            self::REPORT_BUGS  => array(
                'text'          => $translate->_('Report bugs'),
                'description'   => $translate->_('Report bugs to the software vendor directly when they occur.'),
            ),
            self::CHECK_VERSION  => array(
                'text'          => $translate->_('Check version'),
                'description'   => $translate->_('Check for new versions of this software.'),
            ),
            self::MANAGE_OWN_PROFILE  => array(
                'text'          => $translate->_('Manage own profile'),
                'description'   => $translate->_('The right to manage the own profile (selected contact data).'),
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
