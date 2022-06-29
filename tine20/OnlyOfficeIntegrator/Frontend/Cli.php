<?php
/**
 * Tine 2.0
 * @package     OnlyOfficeIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for OnlyOfficeIntegrator
 *
 * This class handles cli requests for the OnlyOfficeIntegrator
 *
 * @package     Filemanager
 */
class OnlyOfficeIntegrator_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = OnlyOfficeIntegrator_Config::APP_NAME;

    public function goIntoMaintenanceMode($opt)
    {
        OnlyOfficeIntegrator_Controller::getInstance()->goIntoMaintenanceMode();
        do {
            if (OnlyOfficeIntegrator_Controller::getInstance()->isInMaintenanceMode()) {
                break;
            }
            sleep(1);
        } while (true);

        return 0;
    }

    public function leaveMaintenanceMode($opt)
    {
        OnlyOfficeIntegrator_Controller::getInstance()->leaveMaintenanceMode();
        return 0;
    }
}
