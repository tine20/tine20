<?php
/**
 * Expresso Lite
 * Replaces the value of a cookie stored within the
 * current user's TineSession
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class ReplaceCookieValue extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $this->tineSession->replaceCookieValue($this->param('cookieName'), $this->param('newValue'));
        return (object) array(
            'status' => 'success'
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
