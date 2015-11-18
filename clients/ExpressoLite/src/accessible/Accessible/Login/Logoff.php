<?php
/**
 * Expresso Lite Accessible
 * Handler for Logoff calls.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Login;

use Accessible\Handler;
use Accessible\Dispatcher;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\ShowFeedback;

class Logoff extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        if (TineSessionRepository::getTineSession()->isLoggedIn()) {
            $liteRequestProcessor = new LiteRequestProcessor();
            $liteRequestProcessor->executeRequest('Logoff', (object) array());
        }

        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'typeMsg' => ShowFeedback::MSG_SUCCESS,
            'message' => 'Saída realizada com sucesso.',
            'destinationText' => 'Acessar a página de login',
            'destinationUrl' => (object) array(
                'action' => 'Login.Main'
            )
        ));
    }
}
