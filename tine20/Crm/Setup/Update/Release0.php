<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Release0.php 2759 2008-06-10 15:52:56Z nelius_weiss $
 */

class Crm_Setup_Update_Release0 extends Setup_Update_Abstract
{
    public function update_0()
    {
    	
    }
    
	/**
     * update function 1
     * renames metacrm_products to metacrm_leads_products
     * renames metacrm_productsource to metacrm_products
     * adds MANAGE_LEADS right to user role
     */    
    public function update_1()
    {
        $this->renameTable('metacrm_product', 'metacrm_leads_products');
        $this->renameTable('metacrm_productsource', 'metacrm_products');
        
        $this->setTableVersion('metacrm_leads_products', '2');
        $this->setTableVersion('metacrm_products', '2');
        
        // add MANAGE_LEADS right to user role
        $userRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
        if ($userRole) {
            $application = Tinebase_Application::getInstance()->getApplicationByName('Crm');
            Tinebase_Acl_Roles::getInstance()->addSingleRight(
                $userRole->getId(), 
                $application->getId(), 
                Crm_Acl_Rights::MANAGE_LEADS
            );                
        }        

        $this->setApplicationVersion('Crm', '0.2');
    }
}
