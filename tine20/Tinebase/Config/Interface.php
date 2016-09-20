<?php
/**
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * interface for config classes, just used to enforce the abstract static function(s)
 *
 * @package     Tinebase
 * @subpackage  Config
 */
interface Tinebase_Config_Interface
{
    /**
     * get properties definitions
     *
     * @return array
     */
    static function getProperties();
}