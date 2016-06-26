<?php
/**
 * Expresso Lite
 * Retrieves all cookies associated with current user's TineSession
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class GetTineCookies extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        return (object) array(
                'cookies' =>  $this->tineSession->getCookies()
        );
    }

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::allowAccessOnlyWithDebuggerModule
     */
    public function allowAccessOnlyWithDebuggerModule()
    {
        return true;
    }
}
