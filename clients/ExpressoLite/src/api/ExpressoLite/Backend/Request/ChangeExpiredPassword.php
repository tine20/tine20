<?php
/**
 * Expresso Lite
 * Handler for changeExpiredPassword calls (self explanatory).
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Exception\LiteException;

class ChangeExpiredPassword extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $userName = $this->param('userName');
        $oldPassword = $this->param('oldPassword');
        $newPassword = $this->param('newPassword');

        $response = $this->jsonRpc('Tinebase.changeExpiredPassword', (object) array(
            'userName' => $userName,
            'oldPassword' => $oldPassword,
            'newPassword' => $newPassword
        ));

        if ($response->result->success === false) {
            throw new LiteException($response->result->errorMessage);
        }
        return $jreq->result;
    }
}
