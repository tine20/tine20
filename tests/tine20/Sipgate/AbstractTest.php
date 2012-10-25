<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Sipgate_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * testconfig
     * @var array
     */
    protected $_testConfig = NULL;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $cfg = Tinebase_Core::getConfig();
        if(!$cfg->sipgate) {
            $this->markTestSkipped('No config to access the sipgate api is available.');
        }
        $this->_testConfig = $cfg->sipgate->toArray();
        $this->_testConfig['description'] = 'unittest';
        $this->_testConfig['accounttype'] = 'plus';
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
}
