<?php
/**
 * Expresso Lite
 * Thrown when AJAX API is called in an environment that
 * lacks php-curl installation
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class CurlNotInstalledException extends LiteException
{
    /**
     * Creates a new <tt>CurlNotInstalledException</tt>.
     * Uses HTTP code 500 - INTERNAL_ERROR
     */
    public function __construct()
    {
        parent::__construct('CurlNotInstalledException', 0, self::HTTP_500_INTERNAL_ERROR);
    }
}
