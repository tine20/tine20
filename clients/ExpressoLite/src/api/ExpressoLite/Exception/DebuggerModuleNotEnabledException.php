<?php
/**
 * Expresso Lite
 * Exception thrown when a LiteRequest with
 * allowAccessOnlyWithDebuggerModule == true is invoked when the debugger
 * module is not made available at conf.php
 *
 * @package   ExpressoLite\Backend\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class DebuggerModuleNotEnabledException extends LiteException
{
    /**
     * Creates a new <tt>DebuggerModuleNotEnabledException</tt>.
     * It defaults parent class httpCode to 401.
     *
     * @param string $message The exception message.
     * @param int $code The exception code used for logging.
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct ( $message, $code, 401 );
    }
}
