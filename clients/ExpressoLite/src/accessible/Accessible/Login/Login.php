<?php
/**
 * Expresso Lite Accessible
 * Handler for Login calls.
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
use Accessible\Core\ShowFeedback;

class Login extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $user = $params->user;
        $password = $params->pwd;
        $liteRequestProcessor = new LiteRequestProcessor();
        $response = $liteRequestProcessor->executeRequest('Login', (object) array(
            'user' => $user,
            'pwd' => $password
        ));

        if ($response->success === true) {
            Dispatcher::processRequest('Mail.Main', $params);
        } else {
            Dispatcher::processRequest('Core.ShowFeedback', (object) array(
                'typeMsg' => ShowFeedback::MSG_ERROR,
                'message' => 'Login falhou! Usuário ou senha inválidos.',
                'destinationText' => 'Voltar para página de login',
                'destinationUrl' => (object) array(
                    'action' => 'Login.Main'
                )
            ));
        }
    }
}
