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
class Tinebase_Delegators_SessionLifetimeTestDelegate implements Tinebase_Session_SessionLifetimeDelegateInterface
{
    /**
     * return session lifetime
     *
     * @return integer
     */
    public function getSessionLifetime($default)
    {
        return 1800;
    }
}