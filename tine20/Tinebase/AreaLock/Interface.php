<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend interface for area locks
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
interface Tinebase_AreaLock_Interface
{
    public function saveValidAuth(): Tinebase_DateTime;

    /**
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    public function hasValidAuth(): bool;

    public function getAuthValidity(): ?Tinebase_DateTime;

    public function resetValidAuth(): void;
}
