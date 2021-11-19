<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */
require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';


/**
 * Abstract test class for Tinebase_WebDav_Plugin_*
 */
abstract class Tinebase_WebDav_Plugin_AbstractBaseTest extends TestCase
{
    /**
     *
     * @var Sabre\DAV\Server
     */
    protected $server;

    /**
     *
     * @var Sabre\HTTP\ResponseMock
     */
    protected $response;

    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()));
        $this->server->debugExceptions = true;

        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    /**
     * Setups a personal calendar
     */
    protected function setupCalendarContainer()
    {
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )));
        Tinebase_Container::getInstance()->increaseContentSequence($this->objects['initialContainer']);
    }
}
