<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Log_Filter_*
 */
class Tinebase_Log_Filter_FilterTest extends PHPUnit_Framework_TestCase
{
    protected $_logger = null;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Log_Filter_FilterTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * test user Filter
     */
    public function testUserFilter()
    {
        $filter = new Tinebase_Log_Filter_User(Tinebase_Core::getUser()->accountLoginName);
        $this->assertTrue($filter->accept(array('message' => 'foo accept bar')));
    }
    
    /**
     * test messsage filter
     */
    public function testMessageFilter()
    {
        $filter = new Zend_Log_Filter_Message('/accept/');
        $this->assertTrue($filter->accept(array('message' => 'foo accept bar')));
        $this->assertFalse($filter->accept(array('message' => 'foo reject bar')));
    }
}
