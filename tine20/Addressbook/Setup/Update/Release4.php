<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

class Addressbook_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * update to 4.1
     * - drop column jpegphoto
     */
    public function update_0()
    {
        try {
            $this->_backend->dropCol('addressbook', 'jpegphoto');
        } catch (Zend_Db_Statement_Exception $zdse) {
            Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
        }
        
        $this->setTableVersion('addressbook', 12);
        
        $this->setApplicationVersion('Addressbook', '4.1');
    }

    /**
     * update to 4.2
     * - do nothing / just increase version number / import definitions are updated in update_2
     * 
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Addressbook', '4.2');
    }
        
    /**
     * update to 4.3
     * 
     * - do nothing / just increase version number / import definitions are updated in update_2
     * 
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Addressbook', '4.3');
    }

    /**
     * update to 4.4
     * - add new outlook / exchange / vcard import definition
     * 
     * @return void
     */
    public function update_3()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setApplicationVersion('Addressbook', '4.4');
    }
    
    /**
     * update to 5.0
     * @return void
     */
    public function update_4()
    {
        $this->setApplicationVersion('Addressbook', '5.0');
    }
}
