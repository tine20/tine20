<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Tinebase exception that is only thrown in the "normal" program flow
 *
 * this exception
 * - is not logged to sentry
 * - is logged with a higher level (NOTICE by default) to log
 *
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_ProgramFlow extends Tinebase_Exception
{
    /**
     * default log level for Tinebase_Exception::log()
     *
     * @var string
     */
    protected $_logLevelMethod = 'notice';
}
