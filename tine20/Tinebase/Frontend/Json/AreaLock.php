<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <c.weiss@metaways.de>
 */

/**
 * Json Adapter class
 * 
 * @package     Tinebase
 * @subpackage  Adapter
 *
 * @todo return lock state in registry data (areaLocks key)
 */
class Tinebase_Frontend_Json_AreaLock extends  Tinebase_Frontend_Json_Abstract
{
    /**
     * @param string $area
     * @param string $password
     * @return array
     */
    public function unlock($area, $password = null)
    {
        $result = Tinebase_AreaLock::getInstance()->unlock($area, $password);

        return $result->toArray();
    }

    /**
     * @param string $area
     * @return array
     */
    public function lock($area)
    {
        $result = Tinebase_AreaLock::getInstance()->lock($area);

        return $result->toArray();
    }

    /**
     * @param string $area
     * @return boolean
     */
    public function isLocked($area)
    {
        $result = Tinebase_AreaLock::getInstance()->isLocked($area);

        return $result;
    }
}
