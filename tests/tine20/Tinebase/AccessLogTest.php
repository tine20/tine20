<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_AccessLog
 */
class Tinebase_AccessLogTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_AccessLog
     */
    protected $_uit = null;

    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_uit = Tinebase_AccessLog::getInstance();
    }

    /**
     * Test create a access log and logout to set logout time
     *
     * @see 0010728: Strange error in tine20 log when performing logout from Web
     *
     * @group nogitlabci
     */
    public function testSetLogout()
    {
        $accessLog = new Tinebase_Model_AccessLog(array(
            'ip'         => '127.0.0.1',
            'li'         => Tinebase_DateTime::now(),
            'result'     => Zend_Auth_Result::SUCCESS,
            'clienttype' => 'unittest',
            'login_name' => 'unittest',
            'user_agent' => 'phpunit',
            'sessionid'  => Tinebase_Record_Abstract::generateUID(),
        ), true);
        $this->_uit->setSessionId($accessLog);
        $this->_uit->create($accessLog);

        $accessLog = $this->_uit->setLogout();

        $now = Tinebase_DateTime::now();
        $this->assertTrue($now->toString() === $accessLog->lo->toString() || $now->subSecond(1)->toString() === $accessLog->lo->toString(),
            'logout time mismatch/empty: ' . print_r($accessLog->toArray(), true));
    }
}
