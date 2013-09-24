<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Admin_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * 
     * @return void
     */
    public function update_0()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Admin'));
        $this->setApplicationVersion('Admin', '7.1');
    }

    /**
     * update to 8.0
     *
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('Admin', '8.0');
    }
}
