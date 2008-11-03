<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Erp_Backend_ProjectTest::main');
}

/**
 * Test class for Erp_Backend_ProjectTest
 */
class Erp_Backend_ProjectTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * the project backend
     *
     * @var Erp_Backend_Project
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Erp Project Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_backend = new Erp_Backend_Project();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
    }
    
    /**
     * create new project
     *
     */
    public function testCreateProject()
    {
        $project = $this->_getProject();
        $created = $this->_backend->create($project);
        
        $this->assertEquals($created->title, $project->title);
        $this->assertGreaterThan(0, $created->number);
        $this->assertEquals($created->container_id, Tinebase_Container::getInstance()->getContainerByName('Erp', 'Shared Projects', 'shared')->getId());
        
        $this->_backend->delete($project);
    }

    /**
     * get project
     *
     * @return Erp_Model_Project
     */
    protected function _getProject()
    {
        return new Erp_Model_Project(array(
            'title'         => 'phpunit project',
            'description'   => 'blabla'
        ), TRUE);
    }
}
