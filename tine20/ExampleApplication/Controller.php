<?php
/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ExampleApplication Controller (composite)
 * 
 * The ExampleApplication 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package ExampleApplication
 * @subpackage  Controller
 */
class ExampleApplication_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    use Tinebase_Controller_SingletonTrait;

    const publicTestRouteOutput = 'publicTestRouteOutput';
    const authTestRouteOutput = 'authTestRouteOutput';

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'ExampleApplication_Model_ExampleRecord';

    protected $_applicationName = 'ExampleApplication';

    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the account object
     * @return Tinebase_Record_RecordSet  of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $personalContainer = Tinebase_Container::getInstance()->createDefaultContainer(
            'ExampleApplication_Model_ExampleRecord',
            'ExampleApplication',
            $_accountId
        );

        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));

        return $container;
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Tinebase_Event_User_DeleteAccount':
                /**
                 * @var Tinebase_Event_User_DeleteAccount $_eventObject
                 */
                if ($_eventObject->deletePersonalContainers()) {
                    $this->deletePersonalFolder($_eventObject->account, ExampleApplication_Model_ExampleRecord::class);
                }
                break;
        }
    }

    /**
     * @param string $parameter
     * @return \Zend\Diactoros\Response
     */
    public function publicTestRoute($parameter = 'a')
    {
        $response = new \Zend\Diactoros\Response();
        $body = $response->getBody();

        if ($parameter !== 'a')
            $body->write($parameter);

        $body->write(self::publicTestRouteOutput);

        return $response;
    }

    /**
     * @return \Zend\Diactoros\Response
     */
    public function authTestRoute()
    {
        $response = new \Zend\Diactoros\Response();
        $body = $response->getBody();

        $body->write(self::authTestRouteOutput);

        return $response;
    }

    /**
     * @param \FastRoute\RouteCollector $routeCollector
     * @return null
     */
    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/ExampleApplication', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/public/testRoute', (new Tinebase_Expressive_RouteHandler(
                ExampleApplication_Controller::class, 'publicTestRoute', [
                    Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
                ]))->toArray());
            $routeCollector->get('/testRoute', (new Tinebase_Expressive_RouteHandler(
                ExampleApplication_Controller::class, 'authTestRoute'))->toArray());
        });

        return null;
    }
}
