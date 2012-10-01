<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * this class handles the rights for the Calendar application
 * 
 * @package     Calendar
 * @subpackage  Acl
 */
class Calendar_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage resources
     * @staticvar string
     */
    const MANAGE_RESOURCES = 'manage_resources';
    
    /**
     * the right to manage shared event favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_EVENT_FAVORITES = 'manage_shared_event_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Calendar_Acl_Rights
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
     * @return Calendar_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Acl_Rights;
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
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS,
            self::MANAGE_SHARED_EVENT_FAVORITES,
            self::MANAGE_RESOURCES,
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
        $translate = Tinebase_Translation::getTranslation('Calendar');
        
        $rightDescriptions = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS => array(
                'text'          => $translate->_('manage shared calendars'),
                'description'   => $translate->_('Create new shared calendars'),
            ),
            self::MANAGE_SHARED_EVENT_FAVORITES => array(
                'text'          => $translate->_('manage shared calendars favorites'),
                'description'   => $translate->_('Create or update shared calendars favorites'),
            ),
            self::MANAGE_RESOURCES => array(
                'text'          => $translate->_('manage resources'),
                'description'   => $translate->_('All Rights to administrate resources')
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
