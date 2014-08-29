<?php
/**
 * Tine 2.0
 * 
 * @package     Courses
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * this class handles the rights for the Courses application
 * 
 * @package     Courses
 * @subpackage  Acl
 */
class Courses_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to add new users to a course
     * 
     * @staticvar string
     */
    const ADD_NEW_USER = 'add_new_user';

    /**
     * the right to add existing users to a course
     * 
     * @staticvar string
     */
    const ADD_EXISTING_USER = 'add_existing_user';
    
    /**
     * the right to manage shared course favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_COURSE_FAVORITES = 'manage_shared_course_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Courses_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     * - disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     */
    private function __construct()
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Courses_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Courses_Acl_Rights;
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
        
        $addRights = array(
            self::ADD_NEW_USER,
            self::ADD_EXISTING_USER,
            self::MANAGE_SHARED_COURSE_FAVORITES,
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
        $translate = Tinebase_Translation::getTranslation('Courses');
        
        $rightDescriptions = array(
            self::ADD_NEW_USER => array(
                'text'          => $translate->_('Add new user'),
                'description'   => $translate->_('Add new user as member to a course')
            ),
            self::ADD_EXISTING_USER => array(
                'text'          => $translate->_('Add existing user'),
                'description'   => $translate->_('Add existing user as member to a course')
            ),
            self::MANAGE_SHARED_COURSE_FAVORITES => array(
                'text'          => $translate->_('Manage shared courses favorites'),
                'description'   => $translate->_('Create or update shared courses favorites'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
