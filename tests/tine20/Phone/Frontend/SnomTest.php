<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Phone_Frontend_Snom
 */
class Phone_Frontend_SnomTest extends Phone_AbstractTest
{
    /**
     * Backend
     *
     * @var Phone_Frontend_Snom
     */
    protected $_snom;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_snom = new Phone_Frontend_Snom();

        // TODO use $request->getServer()->set()
        $_SERVER['PHP_AUTH_USER'] = 'test';
        $_SERVER['PHP_AUTH_PW'] = 'test';
    }

    /**
     * testCallHistory
     */
    public function testCallHistory()
    {
        $mac = $this->_objects['phone1']->macaddress;
        $callId = Tinebase_Record_Abstract::generateUID(20);
        $this->_snom->callHistory($mac, 'outgoing', $callId, '012345', '023456');

        $call = Phone_Controller_Call::getInstance()->get($callId);
        self::assertEquals('012345', $call->source);
    }
}
