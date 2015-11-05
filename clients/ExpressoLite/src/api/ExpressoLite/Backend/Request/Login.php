<?php
/**
 * Expresso Lite
 * Handler for Login calls. This handler connects the current TineSession
 * to the Tine server, allowing it to perform authenticated calls from
 * then on.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Exception\CaptchaRequiredException;

class Login extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        if (! $this->isParamSet('user') || ! $this->isParamSet('pwd')) {
            $this->httpError(400, 'É necessário informar login e senha.');
        }

        try {
            $this->resetTineSession();
            $result = $this->tineSession->login(
                $this->param('user'),
                $this->param('pwd'),
                $this->isParamSet('captcha') ? $this->param('captcha') : null
            );
        } catch (PasswordExpiredException $pe) {
            return (object) array(
                'success' => false,
                'expired' => true
            );
        } catch (CaptchaRequiredException $cre) {
            return (object) array(
                    'success' => false,
                    'captcha' => $cre->getCaptcha()
            );
        }

        if ($result) {
            $cookiePath = str_replace('accessible/', '', $_SERVER['REQUEST_URI']);
            //we remove 'accessible/' suffix from current path.
            //This way, the cookie will always be set to all modules,
            //even if it was started by the accessible module

            setrawcookie( // setrawcookie deals better with @ in the cookie value
                'user', $this->param('user'),
                time()+60*60*24*30, // expires = actual time + 30 days
                $cookiePath
            );

            $_COOKIE['user'] = $this->param('user');
            //setrawcookie() does not update the $_COOKIE array with the new cookie.
            //So, we do this manually to avoid problems with checkIfSessionUserIsValid
            //later on
        }

        $this->checkIfSessionUserIsValid();
        // Its better to check if the tine user matches Expresso Lite user
        // right away

        return (object) array(
            'success' => $result,
            'userInfo' => (object) array (
                'mailAddress' => $this->tineSession->getAttribute('Expressomail.email'),
                'mailSignature' => $this->tineSession->getAttribute('Expressomail.signature'),
                'mailBatch' => MAIL_BATCH,
            )
        );
    }

    /**
     * Allow this request to be called even without a previously estabilished
     * TineSession (after all, THIS is the request that estabilishes the TineSession!)
     *
     * @return true
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
