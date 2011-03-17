<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Addressbook_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * update to 4.1
     * - drop column jpegphoto
     */
    public function update_0()
    {
        $this->_backend->dropCol('addressbook', 'jpegphoto');
        
        $this->setTableVersion('addressbook', 12);
        
        $this->setApplicationVersion('Addressbook', '4.1');
    }

    /**
     * update to 4.2
     * - add new google import definition
     * 
     * @return void
     */
    public function update_1()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Addressbook'));
        
        $this->setApplicationVersion('Addressbook', '4.2');
    }
        
}
