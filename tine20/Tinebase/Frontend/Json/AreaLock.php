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
 * @todo lock state auch in registry data zurückgeben
 * @todo unlock gibt auch lock state ({area' xyz, expires: timestamp USERTIME}) zurück
 */
class Tinebase_Frontend_Json_AreaLock extends  Tinebase_Frontend_Json_Abstract
{
    /**
     * @param $area
     * @param null $password
     * @return boolean
     */
    public function unlock($area, $password = null)
    {
        $result = Tinebase_AreaLock::getInstance()->unlock($area, $password);

        return $result;
    }

    /**
     * @param $area
     * @return boolean
     */
    public function lock($area)
    {
        $result = Tinebase_AreaLock::getInstance()->lock($area);

        return $result;
    }

    /**
     * @param $area
     * @return boolean
     */
    public function isLocked($area)
    {
        $result = Tinebase_AreaLock::getInstance()->isLocked($area);

        return $result;
    }
}
