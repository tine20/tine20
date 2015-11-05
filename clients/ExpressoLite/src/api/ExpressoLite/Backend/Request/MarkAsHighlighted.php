<?php
/**
 * Expresso Lite
 * Handler for markAsHighlighted calls  (self explanatory).
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\Request\Utils\MessageUtils;

class MarkAsHighlighted extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $asHighlighted = $this->param('asHighlighted') === '1';
        $msgIds = explode(',', $this->param('ids'));

        return MessageUtils::addOrClearFlag($this->tineSession, $msgIds, "\\Flagged", $asHighlighted);
    }
}
