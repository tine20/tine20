<?php
/**
 * Expresso Lite
 * Handler for GetContactsByFilter calls.
 *
 * @package Backend
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\Request\Utils\MessageUtils;
class GetContact extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Addressbook.getContact', (object) array(
            'id' => $this->param('id')
        ));

        $tineContact = $response->result;

        if ($tineContact->jpegphoto != 'images/empty_photo_blank.png') {
            $mugshot = MessageUtils::getContactPicture(
                $this->tineSession,
                $tineContact->id,
                strtotime($tineContact->creation_time),
                90, 113);
        } else {
            $mugshot = '';
        }

        return (object) array(
            'id' => $tineContact->id,
            'name' => $tineContact->n_fn,
            'email' => $tineContact->email,
            'phone' => $tineContact->tel_work,
            'mobile' => $tineContact->tel_cell,
            'mugshot'=> $mugshot,
            'otherFields' => $tineContact
        );
    }

}
