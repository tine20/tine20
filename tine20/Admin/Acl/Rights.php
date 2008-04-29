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
     *
     */
    const MANAGE_APPS = 'manage_apps';
    
    /**
     * get all possible application rights
     *
     * @param   Tinebase_Record_RecordSet $_applicationRights  app rights
     * @return  array   all application rights
     * 
     * @todo    get other possible rights from APPNAME_Rights (?) class 
     */
    public function getAllApplicationRights($_applicationId)
    {
        $allRights = parent::getAllApplicationRights($_applicationId);
        
        $addRights = array ( self::MANAGE_APPS );
        $allRights = array_merge ( $allRights, $addRights );
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($allRights, true));
        
        return $allRights;
    }
    
}
