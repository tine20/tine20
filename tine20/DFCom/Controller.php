<?php
/**
 * main controller for DFCom application
 *
 * @package     DFCom
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Device controller class for DFCom application
 *
 * @package     DFCom
 * @subpackage  Controller
 */
class DFCom_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the instance of the singleton
     *
     * @var DFCom_Controller
     */
    private static $_instance = NULL;

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = DFCom_Model_Device::class;

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'DFCom';

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return DFCom_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new DFCom_Controller();
        }

        return self::$_instance;
    }

    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {

    }

    /**
     * @param \FastRoute\RouteCollector $routeCollector
     * @return null
     */
    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/DFCom/v1', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/device/dispatchRecord', (new Tinebase_Expressive_RouteHandler(
                DFCom_Controller_Device::class, 'dispatchRecord', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::PUBLIC_USER_ROLES => [
                    DFCom_Config::PUBLIC_ROLE_NAME
                ],
            ]))->toArray());
            $routeCollector->get('/device/{deviceId}/list/{listId}[/{authKey}]', (new Tinebase_Expressive_RouteHandler(
                DFCom_Controller_DeviceList::class, 'getDeviceList', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::PUBLIC_USER_ROLES => [
                    DFCom_Config::PUBLIC_ROLE_NAME
                ],
            ]))->toArray());
        });

        return null;
    }
}