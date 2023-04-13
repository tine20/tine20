<?php declare(strict_types=1);

/**
 * facade for simpleSAMLphp Auth\Source class
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_SAML_AuthSource extends \SimpleSAML\Auth\Source
{
    /**
     * Log out from this authentication source.
     *
     * This function should be overridden if the authentication source requires special
     * steps to complete a logout operation.
     *
     * If the logout process requires a redirect, the state should be saved. Once the
     * logout operation is completed, the state should be restored, and completeLogout
     * should be called with the state. If this operation can be completed without
     * showing the user a page, or redirecting, this function should return.
     *
     * @param array &$state Information about the current logout operation.
     * @return void
     *
    public function logout(&$state)
    {
        throw new SSO_Facade_SAML_RedirectException('https://localhost:8443/auth/saml2/sp/saml2-logout.php/localhost', null);
    }*/

    /**
     * Reauthenticate an user.
     *
     * This function is called by the IdP to give the authentication source a chance to
     * interact with the user even in the case when the user is already authenticated.
     *
     * @param array &$state Information about the current authentication.
     * @return void
     */
    public function reauthenticate(array &$state)
    {
        $this->authenticate($state);
    }

    public function authenticate(&$state)
    {
        // we need to make sure the state has not set any exception handlers!
        // we want the exceptions we throw, to be thrown all the way back to tine20 code to catch it!
        unset($state[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_URL]);
        unset($state[\SimpleSAML\Auth\State::EXCEPTION_HANDLER_FUNC]);

        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        if (!Tinebase_Core::getUser()) {
            if (isset($request->getParsedBody()['username']) && isset($request->getParsedBody()['password'])) {
                if (! Tinebase_Controller::getInstance()->login($request->getParsedBody()['username'],
                        $request->getParsedBody()['password'],
                        Tinebase_Http_Request::fromString('POST /sso/saml2/redirect HTTP/1.1' . "\r\n\r\n"),
                        'saml2 redirect flow')) {
                    throw new SSO_Facade_SAML_LoginMaskException();
                }
            } else {
                throw new SSO_Facade_SAML_LoginMaskException();
            }
        }

        if ($user = Tinebase_Core::getUser()) {

            $accessLog = new Tinebase_Model_AccessLog(['clienttype' => Tinebase_Frontend_Json::REQUEST_TYPE], true);
            Tinebase_Controller::getInstance()->forceUnlockLoginArea();
            Tinebase_Controller::getInstance()->setRequestContext(array(
                'MFAPassword' => isset($request->getParsedBody()['MFAPassword']) ? $request->getParsedBody()['MFAPassword'] : null,
                'MFAId'       => isset($request->getParsedBody()['MFAUserConfigId']) ? $request->getParsedBody()['MFAUserConfigId'] : null,
            ));
            try {
                Tinebase_Controller::getInstance()->_validateSecondFactor($accessLog, $user);
            } catch (Tinebase_Exception_AreaUnlockFailed | Tinebase_Exception_AreaLocked $tea) { // 630 + 631
                throw new SSO_Facade_SAML_MFAMaskException($tea);
            }
        } else {
            // should never happen, but just in case, lets throw a login mask, that'll be just fine
            // do not not throw! otherwise we would be authenticated and have an undefined state
            throw new SSO_Facade_SAML_LoginMaskException();
        }

        // we are authenticated now...
        $state['Attributes'] = [
            'eduPersonPrincipalName' => [$user->accountDisplayName],
            'uid' => [$user->accountLoginName],
            'firstName' => [$user->accountFirstName],
            'lastName' => [$user->accountLastName],
            'email' => [$user->accountEmailAddress],
        ];
        if (isset($state['SPMetadata'][SSO_Model_Saml2RPConfig::FLD_ATTRIBUTE_MAPPING])) {
            foreach ($state['SPMetadata'][SSO_Model_Saml2RPConfig::FLD_ATTRIBUTE_MAPPING] as $key => $value) {
                if (!$value) {
                    unset($state['Attributes'][$key]);
                } else {
                    $state['Attributes'][$key] = [$user->{$value}];
                }
            }
        }

        if (!isset($state['PersistentAuthData'])) {
            $state['PersistentAuthData'] = [];
        }
        $state['PersistentAuthData'][] = 'SPMetadata';
        if (isset($state['SPMetadata']['customHooks']['postAuthenticate']) && is_readable($state['SPMetadata']['customHooks']['postAuthenticate'])) {
            require $state['SPMetadata']['customHooks']['postAuthenticate'];
        }
    }
}
