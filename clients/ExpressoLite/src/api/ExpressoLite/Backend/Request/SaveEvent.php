<?php
/**
 * Expresso Lite
 * Handler for saveEvent calls (self explanatory).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\TineTunnel\TineJsonRpc;
use ExpressoLite\TineTunnel\TineSession;

class SaveEvent extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $calendarId = $this->param('calendarId');
        $title = $this->param('title');
        $peopleEmails = $this->param('peopleEmails');
        $location = $this->param('location');
        $isWholeDay = $this->param('isWholeDay') == '1';
        $isBlocking = $this->param('isBlocking') == '1' ? 'OPAQUE' : 'TRANSPARENT';
        $notifyPeople = $this->param('notifyPeople') == '1';
        $dtStart = $this->param('dtStart');
        $dtEnd = $this->param('dtEnd');
        $description = $this->param('description');

        $response = $this->jsonRpc('Calendar.saveEvent', (object) array(
            'recordData' => (object) array(
                'container_id' => (object) array('id' => $calendarId),
                'id' => '',
                'dtstart' => $dtStart, // '2000-12-31 06:30:00'
                'dtend' => $dtEnd,
                'originator_tz' => date_default_timezone_get(), // 'America/Sao_Paulo'
                'transp' => $isBlocking,
                'class' => 'PUBLIC',
                'description' => $description,
                'geo' => '',
                'location' => $location,
                'organizer' => (object) array(
                    'account_id' => $this->tineSession->getAttribute('Tinebase.accountId')
                ),
                'priority' => '',
                'status' => 'CONFIRMED',
                'summary' => $title,
                'url' => '',
                'uid' => '',
                'send' => $notifyPeople,
                'attachments' => array(),
                'attendee' => $this->formatAttendees($peopleEmails),
                'alarms' => array(),
                'tags' => array(),
                'notes' => array(),
                'recurid' => '',
                'exdate' => '',
                'rrule' => '',
                'is_all_day_event' => $isWholeDay,
                'rrule_until' => ''
            )
        ));

        return $response;
    }

    /**
     * Returns the formatted objects for attendees, given their email addresses.
     *
     * @param array[string] $strEmails Emails of contacts to be retrieved.
     *
     * @return array[stdClass] Formatted objects for attendees.
     */
    private function formatAttendees($strEmails)
    {
        $users = array((object) array( // first attendee is user himself
            'quantity' => 1,
            'role' => 'REQ',
            'status' => 'ACCEPTED',
            'user_id' => $this->getOwnUserInfo(),
            'user_type' => 'user'
        ));

        $infos = $this->getInfoFromEmail($strEmails);
        foreach ($infos as $info) {
            if ($info->account_id !== null) {
                $users[] = (object) array(
                    'checked' => true, // Tine mistery: user himself doesn't have this member
                    'quantity' => 1,
                    'role' => 'REQ',
                    'status' => 'NEEDS-ACTION',
                    'user_id' => $info,
                    'user_type' => 'user'
                );
            }
        }
        return $users;
    }

    /**
     * Returns information of current user in Tine format.
     *
     * @return stdClass Account info of current user, in Tine format.
     */
    private function getOwnUserInfo()
    {
        return (object) array(
            'contact_id' => $this->tineSession->getAttribute('Expressomail.accountId'),
            'accountDisplayName' => $this->tineSession->getAttribute('Tinebase.accountDisplayName'),
            'accountId' => $this->tineSession->getAttribute('Tinebase.accountId')
        );
    }

    /**
     * Returns information of users in Tine format, given their email addresses.
     *
     * @param array[string] $strEmails Emails of contacts to be retrieved.
     *
     * @return array[stdClass] Account info of users, in Tine format.
     */
    private function getInfoFromEmail($strEmails)
    {
        if (!strlen($strEmails)) return array();

        $users = $this->processor->executeRequest('SearchContactsByEmail', (object) array(
            'emails' => $strEmails,
            'getPicture' => '0'
        ));

        $infos = array();
        foreach ($users as $usr) {
            $infos[] = (object) array(
                'id' => $usr->id,
                'n_fn' => $usr->name,
                'email' => $usr->email,
                'account_id' => $usr->accountId
            );
        }
        return $infos;
    }
}
