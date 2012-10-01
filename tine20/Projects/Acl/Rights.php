<?php
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @todo        add more specific rights
 */

class Projects_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage shared project favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_PROJECT_FAVORITES = 'manage_shared_project_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Projects_Acl_Rights
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
     * @return Projects_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Projects_Acl_Rights;
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
            self::MANAGE_SHARED_PROJECT_FAVORITES,
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
        $translate = Tinebase_Translation::getTranslation('Projects');
        
        $rightDescriptions = array(
            self::MANAGE_SHARED_PROJECT_FAVORITES => array(
                'text'          => $translate->_('Manage shared project favorites'),
                'description'   => $translate->_('Create new shared project favorites'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

    
}
