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
 * Abstract lock implementation
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
abstract class Tinebase_Lock_Abstract implements Tinebase_Lock_Interface
{
    protected $_lockId;

    /**
     * @var bool
     */
    protected $_isLocked = false;

    /**
     * @param string $lockId
     */
    public function __construct($_lockId)
    {
        $this->_lockId = $_lockId;
        $this->processLockId();
    }

    protected function processLockId()
    {
        $this->_lockId = sha1($this->_lockId);
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->_isLocked;
    }
}