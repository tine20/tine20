<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for SendMail_Controller_Event
 * 
 * @package     ActiveSync
 */
class ActiveSync_Command_SendMailTests extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync SendMail Command Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * (non-PHPdoc)
     * @see ActiveSync/ActiveSync_TestCase::setUp()
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $testDevice = ActiveSync_Backend_DeviceTests::getTestDevice();
        
        $this->objects['device'] = ActiveSync_Controller_Device::getInstance()->create($testDevice);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test xml generation for IPhone
     */
    public function testSendMail()
    {
        $stream = fopen(dirname(__FILE__) . '/../../Felamimail/files/text_plain.eml', 'r');
        
        $sendMail = new ActiveSync_Command_SendMail($stream);
        
        $sendMail->handle();
        
        $sendMail->getResponse();
    }
}
