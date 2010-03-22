<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Backend_PolicyTests::main');
}

/**
 * Test class for ActiveSync_Backend_Policy
 * 
 * @package     ActiveSync
 */
class ActiveSync_Backend_PolicyTests extends PHPUnit_Framework_TestCase
{
    
    /**
     * @var ActiveSync_Backend_Policy backend
     */
    protected $_backend;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Backend Policy Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public static function getTestPolicy()
    {
        /*$testPolicy = new ActiveSync_Model_Policy(array(
            'deviceid'      => Tinebase_Record_Abstract::generateUID(64),
            'devicetype'    => 'iPhone',
            'owner_id'      => Tinebase_Core::getUser()->getId(),
            'policy_id'     => 'unset'
        ));*/
    }
    
    protected function setUp()
    {   	
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
}
    
if (PHPUnit_MAIN_METHOD == 'ActiveSync_Backend_Policy::main') {
    ActiveSync_Backend_Policy::main();
}
