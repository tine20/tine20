<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_Cli
 * 
 * @package     Calendar
 */
class Calendar_Frontend_CliTest extends TestCase
{
    /**
     * Backend
     *
     * @var Calendar_Frontend_Cli
     */
    protected $_cli;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_cli = new Calendar_Frontend_Cli();
    }

    /**
     * testSharedCalendarReport
     */
    public function testSharedCalendarReport()
    {
        $calendar = $this->_getTestContainer('Calendar', 'Calendar_Model_Event');
        $this->_setPersonaGrantsForTestContainer($calendar->getId(), 'sclever');

        $opts = new Zend_Console_Getopt('abp:');

        ob_start();
        $this->_cli->sharedCalendarReport($opts);
        $out = ob_get_clean();

        $expectedStrings = [
            '{"' . Tinebase_Core::getUser()->accountLoginName . '":{',
            '{"PHPUnit Calendar_Model_Event container":',
            '{"readGrant":true,"addGrant":true,"editGrant":true,"deleteGrant":true,"privateGrant":false,"exportGrant":false,"syncGrant":false,"adminGrant":false,"freebusyGrant":false',
            '"account_id":"' . $this->_personas['sclever']->getId(),
            '"accountName":"sclever"'
        ];
        foreach ($expectedStrings as $expected) {
            self::assertContains($expected, $out);
        }
    }
}
