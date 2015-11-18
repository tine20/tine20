<?php
/**
 * Expresso Lite
 * Handler for Logoff calls. This disconnects the current TineSession.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class Logoff extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        try {
            $response = $this->tineSession->logout();
        } catch (\Exception $e) {
            error_log('Error during logoff: ' . $e->getMessage());
            $response = array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }

        $this->resetTineSession();
        session_destroy();
        //resetting tine session and detroying the session may be
        //redundant, but its an extra precaution to avoid reuse
        //of invalidated sessions

        return (object) $response;
    }
}
