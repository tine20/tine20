<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class Admin_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_0()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Admin'));
        $this->setApplicationVersion('Admin', '10.0');
    }
}
