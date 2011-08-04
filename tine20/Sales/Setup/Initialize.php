<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     Sales
 */
class Sales_Setup_Initialize extends Setup_Initialize
{
    /**
     * Override method because this app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
        parent::_createInitialRights($_application);
        self::getSharedContractsContainer();
    }
    
    /**
     * get (create if it does not exist) container for shared contracts
     * 
     * @return Tinebase_Model_Container|NULL
     */
    public static function getSharedContractsContainer()
    {
        $sharedContracts = NULL;
        
        try {
            $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', Tinebase_Model_Container::TYPE_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => 'Shared Contracts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId() 
            ));        
            $sharedContracts = Tinebase_Container::getInstance()->addContainer($newContainer);
        }

        // add grants for groups
        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_EDIT
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
            Tinebase_Model_Grants::GRANT_ADD,
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_EDIT,
            Tinebase_Model_Grants::GRANT_DELETE,
            Tinebase_Model_Grants::GRANT_ADMIN
        ), TRUE);
        
        return $sharedContracts;
    }
}
