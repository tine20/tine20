<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Pluggable
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Pluggable
 */
class Tinebase_Pluggable_ConcreteTest extends PHPUnit_Framework_TestCase
{
    protected $frontend = NULL;
    protected $controller = NULL;
    protected $backend = NULL;
    
    /**
     * Sets up the fixture, for example
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        // creates layer instances
        $this->frontend = new Tinebase_Pluggable_DummyFrontend();
        $this->controller = new Tinebase_Pluggable_DummyController();
        $this->backend = new Tinebase_Pluggable_DummyBackend();
        
        // injects plugin into layers
        Tinebase_Frontend_Abstract::attachPlugin('dummyPluginMethod', 'Tinebase_Pluggable_Plugin_DummyPlugin');
        Tinebase_Controller_Abstract::attachPlugin('dummyPluginMethod', 'Tinebase_Pluggable_Plugin_DummyPlugin');
        Tinebase_Backend_Abstract::attachPlugin('dummyPluginMethod', 'Tinebase_Pluggable_Plugin_DummyPlugin');
    }
    
    /**
     * Verifies if plugin is callable from layers
     */
    public function testCallPluginMethod()
    {
        $expected = 'dummyPluginReturn';
        
        $frontendReturn = $this->frontend->dummyPluginMethod();
        $controllerReturn = $this->controller->dummyPluginMethod();
        $backendReturn = $this->backend->dummyPluginMethod();
        
        $this->assertEquals($expected, $frontendReturn);
        $this->assertEquals($expected, $controllerReturn);
        $this->assertEquals($expected, $backendReturn);
    }
}
