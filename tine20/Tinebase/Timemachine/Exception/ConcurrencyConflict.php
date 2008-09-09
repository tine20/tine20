<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Timemachine
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Concurrency Conflict exception
 * 
 * @package     Tinebase
 * @subpackage  Timemachine
 */
class Tinebase_Timemachine_Exception_ConcurrencyConflict extends Exception {
    protected $code = 409;
}