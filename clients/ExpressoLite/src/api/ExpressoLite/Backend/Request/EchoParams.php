<?php
/**
 * Expresso Lite
 * Handler for EchoParams calls.
 * This handler is created only for test purposes.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class EchoParams extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        return $this->params;
    }

    /**
     * Allows this request to be executed even without a previously
     * estabilished TineSession.
     *
     * @return true.
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
