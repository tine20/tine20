<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weisse@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_ImageHelper
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
        $this->_testImagePath = dirname(__FILE__) . '/ImageHelper/phpunit-logo.gif';
        $this->_testImage = Tinebase_Model_Image::getImageFromPath($this->_testImagePath);
        $this->_testImageData = array(
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
        $imgBlob = file_get_contents($this->_testImagePath);
        $imgInfo = Tinebase_ImageHelper::getImageInfoFromBlob($imgBlob);
        
        $this->assertEquals($this->_testImageData['width'], $imgInfo['width']);
    }
    
    /**
     * test that exception gets thrown when given blog does not represent a valid image
     *
     */
    public function testGetImageInfoFromBlobException()
    {
        $rwongBlob = file_get_contents(__FILE__);
        $this->setExpectedException('Tinebase_Exception_UnexpectedValue');
        Tinebase_ImageHelper::getImageInfoFromBlob($rwongBlob);
    }
    
    /**
     * tests if isImageFile is working
     *
     */
    public function testIsImageFile()
    {
        $this->assertTrue(Tinebase_ImageHelper::isImageFile($this->_testImagePath));
        $this->assertFalse(Tinebase_ImageHelper::isImageFile(__FILE__));
    }
    
    /**
     * test preserve and crop resizeing right hand side
     * 
     */
    public function testResizeRatioModePreserveAndCropRight()
    {
        // crop right
        Tinebase_ImageHelper::resize($this->_testImage, 50, 100, Tinebase_ImageHelper::RATIOMODE_PRESERVANDCROP);
        $this->assertEquals(50, $this->_testImage->width);
        // only works on my system^tm
        //$tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        //file_put_contents($tmpPath, $this->_testImage->blob);
        //$this->assertFileEquals(dirname(__FILE__) . '/ImageHelper/phpunit-logo-preserveandcrop-50-100.gif', $tmpPath);
        //unlink($tmpPath);
    }
    /**
     * test preserve and crop resizeing bottom side
     * 
     */
    public function testResizeRatioModePreserveAndCropBottom()
    {
        Tinebase_ImageHelper::resize($this->_testImage, 100, 50, Tinebase_ImageHelper::RATIOMODE_PRESERVANDCROP);
        $this->assertEquals(50, $this->_testImage->height);
        // only works on my system^tm
        //$tmpPath = tempnam(Tinebase_Core::getTempDir(), 'tine20_tmp_gd');
        //file_put_contents($tmpPath, $this->_testImage->blob);
        //$this->assertFileEquals(dirname(__FILE__) . '/ImageHelper/phpunit-logo-preserveandcrop-100-50.gif', $tmpPath);
        //unlink($tmpPath);
    }
    /**
     * test preserve no fill fit width
     */
    public function testResizeRatioModePreservNoFillWidth()
    {
        $testImageRatio = $this->_testImageData['width'] / $this->_testImageData['height'];
        $dstWidth = 100;
        Tinebase_ImageHelper::resize($this->_testImage, $dstWidth, $dstWidth*100, Tinebase_ImageHelper::RATIOMODE_PRESERVNOFILL);
        $this->assertEquals($dstWidth, $this->_testImage->width);
        $this->assertEquals(floor($dstWidth / $testImageRatio), $this->_testImage->height);
        
    }
    /**
     * test preserve no fill fit height
     */
    public function testResizeRatioModePreservNoFillHeight()
    {
        $testImageRatio = $this->_testImageData['width'] / $this->_testImageData['height'];
        $dstHeight = 100;
        Tinebase_ImageHelper::resize($this->_testImage, $dstHeight*100, $dstHeight, Tinebase_ImageHelper::RATIOMODE_PRESERVNOFILL);
        $this->assertEquals($dstHeight, $this->_testImage->height);
        $this->assertEquals(floor($dstHeight * $testImageRatio), $this->_testImage->width);
        
    }
    
    public function testGetBlobNoTouch()
    {
        $blob = $this->_testImage->getBlob('image/gif');
        $this->assertTrue(file_get_contents($this->_testImagePath) == $blob);
    }
    
    public function testGetBlobMaxSize()
    {
        $origBlobSize = strlen($this->_testImage->getBlob('image/jpeg'));
        $maxBlobSize = $origBlobSize/3;
        
        $imageBlobSize = strlen($this->_testImage->getBlob('image/jpeg', $maxBlobSize));
        $this->assertTrue($imageBlobSize <= $maxBlobSize);
    }
}
