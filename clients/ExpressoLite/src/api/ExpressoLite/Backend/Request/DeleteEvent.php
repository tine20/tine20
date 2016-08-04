<?php
/**
 * Expresso Lite
 * Handler for delete calendar events.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class DeleteEvent extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $eventId = $this->param('id');

        return $this->jsonRpc('Calendar.deleteEvents', (object) array(
            'ids' => array($eventId)
        ));
    }
}
