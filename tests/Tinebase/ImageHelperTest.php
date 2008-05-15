<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weisse@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_ImageHelperTest::main();
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ImageHelperTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_ImageHelperTest');
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
        $this->testImagePath = dirname(__FILE__) . '/ImageHelper/phpunit-logo.gif';
        $this->testImageData = array(
            'width'    => 94,
            'height'   => 80,
            'bits'     => 8,
            'channels' => 3,
            'mime'     => 'image/gif'
        );
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
     * test that image info are returned from given blob
     *
     */
    public function testGetImageInfoFromBlob()
    {
        $imgBlob = file_get_contents($this->testImagePath);
        $imgInfo = Tinebase_ImageHelper::getImageInfoFromBlog($imgBlob);

        $this->assertEquals($this->testImageData, $imgInfo);
    }
    
    /**
     * test that exception gets thrown when given blog does not represent a valid image
     *
     */
    public function testGetImageInfoFromBlobException()
    {
        $rwongBlob = file_get_contents(__FILE__);
        $this->setExpectedException('Exception');
        Tinebase_ImageHelper::getImageInfoFromBlog($rwongBlob);
    }
    
    /**
     * tests if isImageFile is working
     *
     */
    public function testIsImageFile() {
        $this->assertTrue(Tinebase_ImageHelper::isImageFile($this->testImagePath));
        $this->assertFalse(Tinebase_ImageHelper::isImageFile(__FILE__));
    }
    
    /**
     * test preserve and crop resizeing
     *
     */
    public function testResizeRatioModePreserveAndCrop() {
        // crop right
        $gdImage = Tinebase_ImageHelper::resize($this->testImagePath, 50, 100, Tinebase_ImageHelper::RATIOMODE_PRESERVANDCROP);
        $tmpPath = tempnam('/tmp', 'tine20_tmp_gd');
        imagegif($gdImage, $tmpPath);
        $this->assertFileEquals(dirname(__FILE__) . '/ImageHelper/phpunit-logo-preserveandcrop-50-100.gif', $tmpPath);
        unset($tmpPath);
        
        // crop bottom
        $gdImage = Tinebase_ImageHelper::resize($this->testImagePath, 100, 50, Tinebase_ImageHelper::RATIOMODE_PRESERVANDCROP);
        $tmpPath = tempnam('/tmp', 'tine20_tmp_gd');
        imagegif($gdImage, $tmpPath);
        $this->assertFileEquals(dirname(__FILE__) . '/ImageHelper/phpunit-logo-preserveandcrop-100-50.gif', $tmpPath);
        unset($tmpPath);
    }
}
