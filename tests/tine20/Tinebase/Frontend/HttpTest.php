<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
        if (version_compare(PHPUnit_Runner_Version::id(), '3.3.0', '<')) {
            $this->markTestSkipped('phpunit version < 3.3.0 cant cope with headers');
        }
        ob_start();
        $this->_uit->mainScreen();
        $html = ob_get_clean();
        
        $this->assertGreaterThan(100, strlen($html));
    }

    /**
     * @group needsbuild
     * @group nogitlabci
     */
    public function testgetPostalXWindow()
    {
        if (version_compare(PHPUnit_Runner_Version::id(), '3.3.0', '<')) {
            $this->markTestSkipped('phpunit version < 3.3.0 cant cope with headers');
        }
        ob_start();
        $this->_uit->getPostalXWindow();
        $html = ob_get_clean();

        $this->assertGreaterThan(100, strlen($html));
    }
}
