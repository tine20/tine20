<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * OnlyOfficeIntegrator exception
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Exception
 */
class OnlyOfficeIntegrator_Exception_WaitForOOSave extends Tinebase_Exception_ProgramFlow
{
    /**
     * the constructor
     *
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        parent::__construct($message ?: 'OO has not yet saved the file. Please wait', 983, $previous);
    }
}
