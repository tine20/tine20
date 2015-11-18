<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 */

/**
 * Tinebase_Exception_MaintenanceMode
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_MaintenanceMode extends Tinebase_Exception
{
    public function __construct($_message='Installation is in maintenance mode. Please try again later', $_code=503) {
        parent::__construct($_message, $_code);
    }
}
