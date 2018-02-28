<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend interface for area locks
 *
 * @package     Tinebase
 * @subpackage  AreaLock
 */
interface Tinebase_AreaLock_Interface
{
    /**
     * @param string $area
     * @return Tinebase_DateTime
     */
    public function saveValidAuth($area);

    /**
     * @param string $area
     * @return bool
     * @throws Exception
     * @throws Zend_Session_Exception
     */
    public function hasValidAuth($area);

    /**
     * @param $area
     * @return bool|Tinebase_DateTime
     */
    public function getAuthValidity($area);

    /**
     * @param string $area
     */
    public function resetValidAuth($area);
}
