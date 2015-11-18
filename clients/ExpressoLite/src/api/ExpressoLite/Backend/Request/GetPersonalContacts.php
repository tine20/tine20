<?php
/**
 * Expresso Lite
 * Handler for getPersonalContacts calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetPersonalContacts extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Addressbook.searchEmailAddresss', (object) array(
            'filter' => array(
                (object) array(
                    'field' => 'query',
                    'label' => 'Pesquisa rápida',
                    'operators' => array(
                        'contains'
                    )
                ),
                (object) array(
                    'field' => 'email_query',
                    'operator' => 'contains',
                    'value' => '@'
                ),
                (object) array(
                    'field' => 'container_id',
                    'operator' => 'equals',
                    'value' => '/personal/'.$this->tineSession->getAttribute('Tinebase.accountId')
                )
            ),
            'paging' => (object) array(
                'dir' => 'ASC',
                'sort' => 'email'
            )
        ));
        $contacts = array();
        foreach ($response->result->results as $cont) {
            $contactsEmail = null;
            if (isset($cont->emails)) { // this contact is actually a group with many emails
                $contactsEmail = array();
                $addrs = explode(',', $cont->emails);
                foreach ($addrs as $addr) {
                    if (strpos($addr, '<') !== false) {
                        $contactsEmail[] = substr($addr, strpos($addr, '<') + 1, strrpos($addr, '>') - strpos($addr, '<') - 1);
                    }
                }
            } else {
                $contactsEmail = array(
                    $cont->email
                ); // ordinary contact, 1 address
            }
            $contacts[] = (object) array(
                'name' => $cont->n_fn,
                'emails' => $contactsEmail
            );
        }
        return $contacts; // both collected and personal contacts, merged
    }
}
