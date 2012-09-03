<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_Model_ATestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syncroton_Model_IDevice
     */
    protected $_testDevice;
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->_testDevice = Syncroton_Backend_DeviceTests::getTestDevice(Syncroton_Model_Device::TYPE_ANDROID);
    }
}
