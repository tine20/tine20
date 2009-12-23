<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * test suite with shared Selenium RC Session
 *
 */
class SessionTestSuite extends PHPUnit_Framework_TestSuite
{
    /* moved to testHelper as it seems like setUp is called in any suite, 
     * whereas tearDown only in the outer one (as expected)
     * we should run our setup code only once per session
    protected function setUp()
    {
        $connection = new SessionTestCase();
        $connection->setBrowser('*firefox');
        $connection->setBrowserUrl('http://localhost/tt/tine20/');
        
        $connection->start();
        $connection->open('http://localhost/tt/tine20/');
        
        $connection->getEval("window.moveBy(-1 * window.screenX, 0); window.resizeTo(screen.width,screen.height);");
        
        $connection->waitForElementPresent('username');
    }
    */
 
    protected function tearDown()
    {
        SessionTestCase::destroySession();
    }
}