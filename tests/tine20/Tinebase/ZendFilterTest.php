<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * test class for zend filter validation
 * 
 * @package     Tinebase
 * @subpackage  Zend_Filter
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_UserTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ZendFilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_UserTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        
        // data to validate
        $this->objects['dataEmptyString'] = array ( 'notEmptyString' => '' );
        $this->objects['dataEmpty'] = array (  );
        $this->objects['dataNotEmptyString'] = array ( 'notEmptyString' => 'not empty' );
        
        // declare filter
        $filters = array();
        $validators = array(
                    'notEmptyString' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required' ),
                );
        $this->objects['filter'] = new Zend_Filter_Input ($filters, $validators);
 
        return;
        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    
    }
    
     /**
     * try to validate dataEmptyString
     *
     */
    public function testZendFilterValidationEmptyString()
    {
        $this->objects['filter']->setData($this->objects['dataEmptyString']);
        
        $this->assertFalse($this->objects['filter']->isValid());
    }

     /**
     * try to validate dataEmpty
     *
     */
    public function testZendFilterValidationEmpty()
    {
        $this->objects['filter']->setData($this->objects['dataEmpty']);

        $this->assertFalse($this->objects['filter']->isValid());
    }

     /**
     * try to validate dataNotEmptyString
     *
     */
    public function testZendFilterValidationNotEmptyString()
    {
        $this->objects['filter']->setData($this->objects['dataNotEmptyString']);
        
        $this->assertTrue($this->objects['filter']->isValid());
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_UserTest::main') {
    Tinebase_UserTest::main();
}
