<?php
/**
 * Expresso Lite
 * Handler for updateMessageCache calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class UpdateMessageCache extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Expressomail.updateMessageCache', (object) array(
            'folderId' => $this->param('folderId'),
            'time' => 10 // minutes?
        ));

        return (object) array(
            'id' => $response->result->id,
            'totalMails' => $this->coalesce($response->result, 'cache_totalcount', 0),
            'unreadMails' => $this->coalesce($response->result, 'cache_unreadcount', 0),
            'recentMails' => $this->coalesce($response->result, 'cache_recentcount', 0)
        );
    }
}
