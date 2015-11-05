<?php
/**
 * Expresso Lite
 * Handler for deleteMessages calls  (self explanatory).
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

class DeleteMessages extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $msgIds = explode(',', $this->param('messages'));
        $forever = $this->isParamSet('forever') && $this->param('forever') === '1';

        if ($forever) {
            return MessageUtils::addOrClearFlag($this->tineSession, $msgIds, "\\Deleted", true);
        } else {
            return MessageUtils::moveMessages($this->tineSession, $msgIds, '_trash_');
        }
    }
}
