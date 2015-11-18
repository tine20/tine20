<?php
/**
 * Expresso Lite
 * Handler for searchContactsByToken calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class SearchContactsByToken extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $token = $this->param('token');
        $start = $this->isParamSet('start') ? $this->param('start') : 0; // pagination
        $limit = $this->isParamSet('limit') ? $this->param('limit') : 50;

        $response = $this->jsonRpc('Addressbook.searchContacts', (object) array(
            'filter' => array(
                (object) array( // search on all catalogs
                    'field' => 'query',
                    'id' => 'quickFilter',
                    'operator' => 'contains',
                    'value' => $token
                )
            ),
            'paging' => (object) array(
                'dir' => 'ASC',
                'limit' => $limit,
                'sort' => 'n_fileas',
                'start' => $start
            )
        ));
        $contacts = array();
        foreach ($response->result->results as $contact) {
            $contacts[] = (object) array(
                'cpf' => $contact->id, // yes, returned object is inconsistent, see searchContactsByEmail()
                'email' => $contact->email,
                'name' => $contact->n_fn,
                'isDeleted' => $contact->is_deleted !== '',
                'org' => $contact->org_unit
            );
        }
        return (object) array(
            'contacts' => $contacts,
            'totalCount' => $response->result->totalcount // useful for pagination
        );
    }
}
