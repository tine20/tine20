<?php
/**
 * Tine Tunnel
 * Interface that handles cookies detected in a Request response.
 *
 * @package   ExpressoLite\TineTunnel
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\TineTunnel;

interface CookieHandler
{

    /**
     * Stores a cookie so it can be retrieved later.
     *
     * @param $cookie An object with two attributes: $cookie->name and $cookie->value.
     *
     */
    public function storeCookie($cookie);

    /**
     * Returns an array with all stored cookies.
     *
     * @return array with all stored cookies.
     *
     */
    public function getCookies();

    /**
     * Deletes an specific cookie
     *
     * @param string $cookieName Name of the cookie to be deleted.
     */
    public function deleteCookie($cookieName);
}
