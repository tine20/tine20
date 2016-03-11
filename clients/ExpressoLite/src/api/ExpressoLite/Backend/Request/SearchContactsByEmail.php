<?php
/**
 * Expresso Lite
 * Handler for searchContactsByEmail calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\TineTunnel\Request;
use ExpressoLite\TineTunnel\TineJsonRpc;

class SearchContactsByEmail extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $emails = explode(',', $this->param('emails'));
        $getPicture = $this->param('getPicture') == '1';

        $filters = array();
        foreach ($emails as $email) {
            $filters[] = (object) array(
                'field' => 'email_query',
                'operator' => 'contains',
                'value' => $email
            );
        }
        $response = $this->jsonRpc('Addressbook.searchContacts', (object) array(
            'filter' => array(
                (object) array(
                    'condition' => 'OR',
                    'filters' => array(
                        (object) array(
                            'condition' => 'OR',
                            'filters' => $filters,
                            'label' => 'Contacts'
                        )
                    )
                )
            ),
            'paging' => (object) array(
                'sort' => 'n_fn',
                'dir' => 'ASC',
                'start' => 0,
                'limit' => 50 //count($emails)
            )
        ));
        $contacts = array();
        foreach ($response->result->results as $contact) {
            $contacts[] = (object) array(
                'id' => $contact->id,
                'accountId' => $contact->account_id,
                'isDeleted' => $contact->is_deleted !== '0',
                'created' => strtotime($contact->creation_time),
                'email' => $contact->email,
                'mugshot' => $getPicture ? $this->getContactPicture($contact->id, strtotime($contact->creation_time), 90, 113) : null,
                //'mugshotUrl' => $this->assemblyMugshotUrl($contact->id, strtotime($contact->creation_time), 90, 113),
                'name' => $contact->n_fn,
                'phone' => $contact->tel_work,
                'cpf' => sprintf('%011d', (int) $contact->account_id),
                'org' => $contact->org_name,
                'orgUnit' => $contact->org_unit
            );
        }
        return $this->removeDuplicatedContacts($contacts);
    }

    /**
     * Returns the binary content of a contact picture
     *
     * @param $contactId The id of the contact
     * @param $creationTime The creation time of the contact
     * @param $cx The desired picture width
     * @param $cy The desired picture height
     *
     * @return The binary contact of the contact picture.
     */
    public function getContactPicture($contactId, $creationTime, $cx, $cy)
    {
        //we need to make a 'custom' request to get the contact picture, so
        //we build a Request object manually instead of relying on tineSession
        //to do it for us
        $req = new Request();
        $req->setBinaryOutput(false); // do not directly output the binary stream to client
        $req->setCookieHandler($this->tineSession); //tineSession has the necessary cookies
        $req->setUrl($this->assemblyMugshotUrl($contactId, $creationTime, $cx, $cy));
        $req->setHeaders(array(
            'Connection: keep-alive',
            'DNT: 1',
            'User-Agent: ' . TineJsonRpc::DEFAULT_USERAGENT,
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        ));
        $mugshot = $req->send();

        return ($mugshot === null || substr($mugshot, 0, 14) === '<!DOCTYPE html') ?
            '' : $mugshot; // dummy if binaryOutput(true)
    }

    /**
     * Assemblies the URL to retrieve the mugshot of a given contact.
     *
     * @param $contactId    The ID of the contact.
     * @param $creationTime Timestamp of contact creation date.
     * @param $cx           Desired picture width, in pixels.
     * @param $cy           Desired picture height, in pixels.
     *
     * @return Mugshot URL for the contact.
     */
    private function assemblyMugshotUrl($contactId, $creationTime, $cx, $cy)
    {
        return $this->tineSession->getTineUrl() .
            '?method=Tinebase.getImage' .
            '&application=Addressbook' .
            '&location=' .
            '&id='.$contactId .
            '&width='.$cx .
            '&height='.$cy .
            '&ratiomode=0' .
            '&mtime='.$creationTime.'000';
    }

    /**
     * This method ensures that there is only one single contact for each e-mail,
     * removing duplicate contacts. Note that in these cases, this method will always
     * keep the contact with a mugshot over one without it.
     *
     * @param array $contacts The array of contacts to be trimmed of duplicates
     *
     * @return The $array without duplicate contacts
     */
    private function removeDuplicatedContacts(array $contacts)
    {
        $ret = array();
        foreach ($contacts as $contact) {
            $willAdd = true;
            for ($i = 0, $tot = count($ret); $i < $tot; ++ $i) {
                if ($contact->email === $ret[$i]->email) { // duplicated contact
                    if ($ret[$i]->mugshot == '' || $ret[$i]->mugshot === null) { // our current contact has no mugshot
                        if ($contact->mugshot != '' && $contact->mugshot !== null)
                            $ret[$i] = $contact; // replace
                    }
                    $willAdd = false;
                    break;
                }
            }
            if ($willAdd)
                $ret[] = $contact;
        }
        return $ret;
    }
}
