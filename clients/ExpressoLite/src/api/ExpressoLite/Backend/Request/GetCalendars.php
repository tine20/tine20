<?php
/**
 * Expresso Lite
 * Returns all available calendars for current user.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class GetCalendars extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $accountId = $this->tineSession->getAttribute('Tinebase.accountId');
        return array_merge(
            $this->getCalendarsOf('personal', $accountId),
            $this->getCalendarsOf('shared', false), // corporate
            $this->getSharedCalendars()
        );
    }

    /**
     * Retrieves specific calendars matching the given criteria.
     *
     * @param string $containerType Confuse parameter of Tine request, just forwarded.
     * @param string $owner         Filter calendar by owner, "false" disables filter.
     *
     * @return array[stdClass] All calendars matching the criteria.
     */
    private function getCalendarsOf($containerType, $owner)
    {
        $calendars = $this->jsonRpc('Tinebase_Container.getContainer', (object) array(
            'application' => 'Calendar',
            'containerType' => $containerType,
            'owner' => $owner,
            'requiredGrants' => null
        ));

        $clean = array();
        foreach ($calendars->result as $c) {
            $clean[] = (object) array( // sanitize calendar object
                'id' => $c->id,
                'name' => $c->name,
                'owner' => isset($c->owner) ? $c->owner->accountDisplayName : '',
                'color' => $c->color
            );
        }
        return $clean;
    }

    /**
     * Retrieves all calendars which have been shared with current user.
     *
     * @return array[stdClass] All shared calendar objects.
     */
    private function getSharedCalendars()
    {
        $people = $this->jsonRpc('Tinebase_Container.getContainer', (object) array(
            'application' => 'Calendar',
            'containerType' => 'otherUsers',
            'owner' => false,
            'requiredGrants' => null
        ));

        $calendars = array();
        foreach ($people->result as $p) { // each person may have one or more shared calendars
            $calendars = array_merge($calendars,
                $this->getCalendarsOf('personal', $p->accountId));
        }
        return $calendars;
    }
}
