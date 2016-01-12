<?php
/**
 * Expresso Lite
 * Handler for getContactsByFilter calls.
 *
 * @package Backend
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetContactsByFilter extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Addressbook.searchContacts', (object) array(
            'filter' => $this->createFilter(),
            'paging' => $this->createPaging()
        ));
        $contacts = $this->convertContactsFromTineToLite($response->result->results);

        $result = (object) array(
            'contacts' => $contacts,
            'totalCount' => $response->result->totalcount
        );
        return $result;
    }

    /**
     * Creates a filter object to be sent to Addressbook.searchContacts
     *
     * @return The filter object
     */
    private function createFilter()
    {
        $filters = array();
        if ($this->isParamSet('query')) {
            $filters[] = (object) array(
                'field' => 'query',
                'operator' => 'contains',
                'value' => $this->param('query')
            );
        }
        if ($this->isParamSet('containerPath')) {
            $filters[] = (object) array(
                'field' => 'container_id',
                'operator' => 'in',
                'value' => array(
                    (object) array(
                        'path' => $this->param('containerPath')
                    )
                )
            );
        }
        return array(
            (object) array(
                'condition' => 'AND',
                'filters' => $filters
            )
        );
    }

    /**
     * Creates a paging object to be sent to Addressbook.searchContacts
     *
     * @return The paging object
     */
    private function createPaging()
    {
        return (object) array(
            'sort' => array(
                'n_fn',
                'id'
            ),
            'dir' => 'ASC',
            'start' => $this->param('start'),
            'limit' => $this->param('limit')
        );
    }

    /**
     * Compares two values. If the $v1 > $v2, returns 1. If the $v1 < $v2, returns -1.
     * If the two values are equal, returns 0. This is used by function compareContacts
     * @see ExpressoLite\Backend\Request\LiteRequest::compareContacts
     *
     * @param $v1 The first value to be compared
     * @param $v2 The second value to be compared
     *
     * @return int A value indicating which param is the greatest
     */
    private static function cmp($v1, $v2) {
        if ($v1 == $v2) {
            return 0;
        } else {
            return $v1 < $v2 ? -1 : 1;
        }
    }


    /**
     * Compares two contacts by name and then by id. Will return an int value indicating
     * which contact should be first. This will be used for contact sorting.
     * @see ExpressoLite\Backend\Request\LiteRequest::cmp
     *
     * @param $c1 The first contact to be compared
     * @param $c2 The second contact to be compared
     *
     * @return int A value indicating which contact should be placed first
     */
    private static function compareContacts($c1, $c2) {

        $cmpRes = self::cmp(strtolower($c1->name), strtolower($c2->name));
        if ($cmpRes == 0) {
            return self::cmp($c1->id, $c2->id);
        } else {
            return $cmpRes;
        }
    }

    /**
     * Converts an array of contacts in the format returned by Tine to
     * an array of contacts in the format expected by Lite.
     *
     * @param $tineContacts An array of contacts as returned by Tine
     *
     * @return array An array of contacts in the format expected by Lite.
     */
    public function convertContactsFromTineToLite($tineContacts)
    {
        $liteContacts = array();
        $first = true;
        foreach ($tineContacts as $tineContact) {
            $liteContact = (object) array(
                'id' => $tineContact->id,
                'name' => $tineContact->n_fn,
                'email' => $tineContact->email,
                'phone' => $tineContact->tel_work,
                'mobile' => $tineContact->tel_cell,
            );
            $liteContacts[] = $liteContact;
        }

        //tine contacts sorting is broken, we have to sort the results ourselves
        usort($liteContacts, array('ExpressoLite\\Backend\\Request\\GetContactsByFilter', 'compareContacts'));

        return $liteContacts;
    }
}
