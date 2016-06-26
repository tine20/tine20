<?php
/**
 * Expresso Lite
 * Retrieves user information saved first by Login.php handler.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class GetUserInfo extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        return (object) array(
            'mailAddress' => $this->tineSession->getAttribute('Expressomail.email'),
            'mailSignature' => $this->tineSession->getAttribute('Expressomail.signature'),
            'mailBatch' => MAIL_BATCH,
            'showDebuggerModule' => (defined('SHOW_DEBUGGER_MODULE') && SHOW_DEBUGGER_MODULE === true) ? 'show' : 'hide'
        );
    }

    /**
     * Allow this request to be called even without a previously estabilished
     * TineSession, because Login handler may call it.
     *
     * @return true
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
