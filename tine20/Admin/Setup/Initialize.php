<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Class for Admin initialization
 * 
 * @package     Setup
 */
class Admin_Setup_Initialize extends Setup_Initialize
{
    
    /**
     * Override method because admin app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     * 
     * @todo make hard coded role name ('admin role') configurable
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
        //do not call parent::_createInitialRights(); because this app is fopr admins only
        
        $roles = Tinebase_Acl_Roles::getInstance();
        $adminRole = $roles->getRoleByName('admin role');
        $allRights = Tinebase_Application::getInstance()->getAllRights($_application->getId());
        foreach ( $allRights as $right ) {
            $roles->addSingleRight(
                $adminRole->getId(), 
                $_application->getId(), 
                $right
            );
        }     
    }
}
