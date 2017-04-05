<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * session lifetime test delegate
 *
 * @package     Tinebase
 * @subpackage  Session
 */
class Tinebase_Delegators_DelegateTest extends TestCase
{
    public function testSessionLifetimeDelegate()
    {
        Tinebase_Core::clearDelegatorCache();
        Tinebase_Config::getInstance()->sessionLifetimeDelegate = 'Tinebase_Delegators_SessionLifetimeTestDelegate';

        $sessionLifetime = Tinebase_Session_Abstract::getSessionLifetime();
        $this->assertEquals(1800, $sessionLifetime);
    }
}