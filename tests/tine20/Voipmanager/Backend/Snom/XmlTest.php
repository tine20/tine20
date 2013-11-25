<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Voipmanager_Backend_Snom_XmlTest
 */
class Voipmanager_Backend_Snom_XmlTest extends Voipmanager_Backend_Snom_AbstractTest
{
    /**
     * Backend
     *
     * @var Voipmanager_Backend_Snom_Xml
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Snom Phone Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_backend = new Voipmanager_Backend_Snom_Xml();
    }
    
    /**
     * testGetConfig
     * 
     * @see 0009286: fix generating xml of user settings
     */
    public function testGetConfig()
    {
        $config = $this->_backend->getConfig($this->_objects['phone']);
        
        $this->assertContains('<language perm="RW">Deutsch</language>', $config);
    }
}
