<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Release0.php 9867 2009-08-11 13:37:53Z p.schuele@metaways.de $
 */

class Timetracker_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * move ods export config to config table
     * -> disabled / we don't need that any more (@see update_1)
     * 
     * @return void
     */
    public function update_0()
    {
        /*
        $config = Tinebase_Core::getConfig();
        if (isset($config->timesheetExport)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Saving timesheet ods default export config in database.');
            $tsExportConfig['timesheets']['default'] = $config->timesheetExport->toArray();
            Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::ODSEXPORTCONFIG, Zend_Json::encode($tsExportConfig), 'Timetracker');
        }
        */
        
        $this->setApplicationVersion('Timetracker', '3.1');
    }

    /**
     * move ods export config to import export definitions
     * 
     * @return void
     */
    public function update_1()
    {
        // remove Tinebase_Model_Config::ODSEXPORTCONFIG
        Tinebase_Config::getInstance()->deleteConfigForApplication('odsexportconfig', 'Timetracker');
        
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Timetracker'));
        
        $this->setApplicationVersion('Timetracker', '3.2');
    }
}
