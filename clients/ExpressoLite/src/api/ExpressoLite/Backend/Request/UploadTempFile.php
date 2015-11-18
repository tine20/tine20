<?php
/**
 * Expresso Lite
 * Handler for uploadTempFile calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\Request\Utils\MessageUtils;

class UploadTempFile extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $fileType = isset($_SERVER['HTTP_X_FILE_TYPE']) ?
            $_SERVER['HTTP_X_FILE_TYPE'] : 'text/plain';

        if (isset($_SERVER['HTTP_X_FILE_NAME']) && isset($_SERVER['HTTP_X_FILE_TYPE'])) {
            return MessageUtils::uploadTempFile(
                $this->tineSession,
                file_get_contents('php://input'),
                $_SERVER['HTTP_X_FILE_NAME'],
                $fileType
            );
        } else {
            return $this->httpError(400, 'Nenhum arquivo a ser carregado.');
        }
    }
}
