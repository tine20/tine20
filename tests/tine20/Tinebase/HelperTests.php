<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Helper
 */
class Tinebase_HelperTests extends PHPUnit_Framework_TestCase
{
    public function testArray_value()
    {
        $array = array(
            0     => 'foo',
            'one' => 'bar'
        );
        
        $this->assertEquals('foo', Tinebase_Helper::array_value(0, $array));
        $this->assertEquals('bar', Tinebase_Helper::array_value('one', $array));
    }
    
    public function testArrayHash()
    {
        $hash = Tinebase_Helper::arrayHash(array('foo' => 'bar'));
        
        $this->assertEquals('37b51d194a7513e45b56f6524f2d51f2', $hash);
        
        $hash = Tinebase_Helper::arrayHash(array('foo' => 'bar'), true);
        
        $this->assertEquals('3858f62230ac3c915f300c664312c63f', $hash);
    }
    
    public function testGetDevelopmentRevision()
    {
        $rev = Tinebase_Helper::getDevelopmentRevision();
        $this->assertFalse(empty($rev));
    }
    
    public function testConvertToBytes()
    {
        $this->assertEquals(1024, Tinebase_Helper::convertToBytes('1024'));
        $this->assertEquals(1024, Tinebase_Helper::convertToBytes('1K'));
        $this->assertEquals(1024*1024, Tinebase_Helper::convertToBytes('1M'));
        $this->assertEquals(1024*1024*1024, Tinebase_Helper::convertToBytes('1G'));
        
    }

    /**
     * testSearchArrayByRegexpKey
     * 
     * @see 0008782: Endless loop login windows when calling Active Sync Page
     */
    public function testSearchArrayByRegexpKey()
    {
        $server = array(
            'REMOTE_USER' => '1',
            'REDIRECT_REMOTE_USER' => '2',
            'REDIRECT_REDIRECT_REMOTE_USER' => '3',
            'OTHER' => '4',
        );
        
        $remoteUserValues = Tinebase_Helper::searchArrayByRegexpKey('/REMOTE_USER$/', $server);
        
        $this->assertTrue(! empty($remoteUserValues));
        $this->assertEquals(3, count($remoteUserValues));
        $firstServerValue = array_shift($remoteUserValues);
        $this->assertEquals('1', $firstServerValue);
    }

    public function testIdnaConvert()
    {
        $input = 'andre@xn--brse-5qa.xn--knrz-1ra.info';
        self::assertEquals('andre@börse.knürz.info', Tinebase_Helper::convertDomainToUnicode($input));

        $input = 'nörgler.com';
        self::assertEquals('xn--nrgler-wxa.com', Tinebase_Helper::convertDomainToPunycode($input));
    }
}
