<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TineInitial.php 9535 2009-07-20 10:30:05Z p.schuele@metaways.de $
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

        $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
        $adminGroup = $groupsBackend->getDefaultAdminGroup();
        $userGroup  = $groupsBackend->getDefaultGroup();
        
        try {
            $sharedContracts = Tinebase_Container::getInstance()->getContainerByName('Sales', 'Shared Contracts', Tinebase_Model_Container::TYPE_SHARED);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                Tinebase_Model_Grants::READGRANT,
                Tinebase_Model_Grants::EDITGRANT
            ), TRUE);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
                Tinebase_Model_Grants::ADDGRANT,
                Tinebase_Model_Grants::READGRANT,
                Tinebase_Model_Grants::EDITGRANT,
                Tinebase_Model_Grants::DELETEGRANT,
                Tinebase_Model_Grants::ADMINGRANT
            ), TRUE);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Sales application not found.');
        }
    }
}