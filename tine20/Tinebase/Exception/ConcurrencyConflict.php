<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Concurrency Conflict exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_ConcurrencyConflict extends Tinebase_Exception_ProgramFlow
{
    protected $code = 409;
}
