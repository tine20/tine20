<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Exception
 */
class Tinebase_ExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testAppName()
    {
        $e = new Tinebase_Exception('test', 123);

        $this->assertEquals('Tinebase', $e->getAppName());
        $this->assertEquals(123, $e->getCode());
        $this->assertEquals('test', $e->getMessage());
    }
    
    /**
     * @see 0008794: Create Exception Handler Dialog with inputs
     *      https://forge.tine20.org/mantisbt/view.php?id=8794
     */
    public function testTitle()
    {
        $e = new Tinebase_Exception('test', 321);
        $this->assertEquals('Exception ({0})', $e->getTitle());
    }
}
