<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Admin_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update to 3.1
     * - add DEFAULTINTERNALADDRESSBOOK
     * @return void
     */
    public function update_0()
    {
        $settings = Admin_Controller::getInstance()->getConfigSettings();
        
        if (! array_key_exists(Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK, $settings)) {
            try {
                $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
                Admin_Controller::getInstance()->saveConfigSettings(array(
                    Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK => $internalAddressbook->getId()
                ));
            } catch (Tinebase_Exception_NotFound $tenf) {
                // do nothing
            }
        }
        
        $this->setApplicationVersion('Admin', '3.1');
    }
    
    /**
     * update to 4.0
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Admin', '4.0');
    }
}
