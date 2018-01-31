<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Lock
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Lock interface
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
interface Tinebase_Lock_Interface
{
    /**
     * @param string $lockId
     */
    public function __construct($lockId);

    /**
     * @return bool
     */
    public function tryAcquire();

    /**
     * @return bool
     */
    public function release();

    /**
     * @return bool
     */
    public function isLocked();
}