<?php
/**
 * Expresso Lite
 * Handler for empty email messages in the trash folder.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

use ExpressoLite\Exception\TineErrorException;

class EmptyTrash extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        try {
            $response = $this->jsonRpc('Expressomail.emptyFolder', (object) array(
                'folderId' => $this->param('trashId')
            ));
            return (object) array(
                'status' => true
            );
        } catch (TineErrorException $te) {
            return (object) array(
                'status' => false,
                'message' => $te->getMessage()
            );
        }
    }
}
