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
 * Interface for runtime session lifetime configuration
 *
 * @package     Tinebase
 * @subpackage  Session
 */
interface Tinebase_Session_SessionLifetimeDelegateInterface
{
    /**
     * return session lifetime
     *
     * @param integer $default
     * @return integer
     */
    public function getSessionLifetime($default);
}