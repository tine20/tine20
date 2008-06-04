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
 * @todo        extend Tinebase_Application_Rights_Abstract and removed no longer needed code
 */

/**
 * this class handles the rights for a given application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Acl_Rights
{
    /**
     * the right to be an administrative account for an application
     *
     */
    const ADMIN = 'admin';
        
    /**
     * the right to run an application
     *
     */
    const RUN = 'run';
    
    /**
     * the right to manage shared tags
     */
    const MANAGE_SHARED_TAGS = 'manage_shared_tags';
    
    /**
     * holdes the instance of the singleton
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
     * @param   Tinebase_Record_RecordSet $_applicationRights  app rights (empty/NULL -> Tinebase)
     * @return  array   all application rights
     * 
     */
    public function getAllApplicationRights($_applicationId = NULL)
    {
        // check if tinebase application
        if ( $_applicationId === NULL ) {
            $allRights = array ( self::MANAGE_SHARED_TAGS );
        } else {
            $allRights = array ( self::RUN, self::ADMIN );
        }
        
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
            self::ADMIN                 => array(
                'text'          => $translate->_('admin'),
                'description'   => $translate->_('admin right description'),
            ),
            self::RUN                   => array(
                'text'          => $translate->_('run'),
                'description'   => $translate->_('run right description'),
            ),
            self::MANAGE_SHARED_TAGS    => array(
                'text'          => $translate->_('manage shared tags'),
                'description'   => $translate->_('manage shared tags right description'),
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
        $result = array(
            'text'          => $_right,
            'description'   => $_right . " right",
        );
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
    
}
