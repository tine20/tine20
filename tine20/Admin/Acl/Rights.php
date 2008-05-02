<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add more specific rights
 */

/**
 * this class handles the rights for the admin application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * @package     Tinebase
 * @subpackage  Acl
 */
class Admin_Acl_Rights extends Tinebase_Acl_Rights
{
   /**
     * the right to manage applications
     * @staticvar string
     */
    const MANAGE_APPS = 'manage_apps';
    
   /**
     * the right to manage roles
     * @staticvar string
     */
    const MANAGE_ROLES = 'manage_roles';

    /**
     * application name
     * @staticvar string
     */
    const APP_NAME = 'Admin';
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        // get application id
        $appId = Tinebase_Application::getInstance()->getApplicationByName(self::APP_NAME)->getId();
        
        $allRights = parent::getAllApplicationRights($appId);
        
        $addRights = array ( 
            self::MANAGE_APPS, 
            self::MANAGE_ROLES, 
        );
        $allRights = array_merge ( $allRights, $addRights );
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($allRights, true));
        
        return $allRights;
    }
    
}
