<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Frontend_Http
 */
class Tinebase_Frontend_HttpTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Frontend_Http
     */
    protected $_uit = NULL;
    
    public function setUp()
    {
        $this->_uit = new Tinebase_Frontend_Http;
    }

    /**
     * @group needsbuild
     */
    public function testMainScreen()
    {
        $response = $this->_uit->mainScreen();
        self::assertEquals(200, $response->getStatusCode());
        self::assertGreaterThan(100, strlen($response->getBody()));
    }

    /**
     * @group needsbuild
     * @group nogitlabci
     */
    public function testgetPostalXWindow()
    {
        $response = $this->_uit->getPostalXWindow();
        self::assertEquals(200, $response->getStatusCode());
        self::assertGreaterThan(100, strlen($response->getBody()));
    }
}
