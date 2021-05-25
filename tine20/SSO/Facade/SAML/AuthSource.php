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
     */
    public function logout(&$state)
    {
        throw new SSO_Facade_SAML_RedirectException('https://localhost:8443/auth/saml2/sp/saml2-logout.php/localhost', null);
    }

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
            $areaLock = Tinebase_AreaLock::getInstance();
            $userConfigIntersection = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
            if ($areaLock->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN) &&
                $areaLock->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN)) {
                foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN) as $areaConfig) {
                    $userConfigIntersection->mergeById($areaConfig->getUserMFAIntersection($user));
                }

                // user has no 2FA config -> currently its sort of optional -> no check
                if ($userConfigIntersection->count() === 0) {
                    $areaLock->forceUnlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
                } else {

                    if (isset($request->getQueryParams()['mfaid'])) {
                        $mfaId = $request->getQueryParams()['mfaid'];
                        $userCfg = $userConfigIntersection->getById($mfaId);

                        if (isset($request->getQueryParams()['mfa'])) {
                            foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN)->filter(function ($rec) use ($userCfg) {
                                return in_array($userCfg->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID}, $rec->{Tinebase_Model_AreaLockConfig::FLD_MFAS});
                            }) as $areaCfg) {
                                if (!$areaCfg->getBackend()->hasValidAuth()) {
                                    $areaLock->unlock(
                                        $areaCfg->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME},
                                        $mfaId,
                                        $request->getQueryParams()['mfa'],
                                        $user
                                    );
                                    break;
                                }
                            }
                        } else {
                            if (!Tinebase_Auth_MFA::getInstance($userCfg
                                    ->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID})->sendOut($userCfg)) {
                                throw new Tinebase_Exception('mfa send out failed');
                            } else {
                                throw new SSO_Facade_SAML_MFAMaskException();
                            }
                        }
                    }

                    if ($areaLock->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN)) {
                        throw new SSO_Facade_SAML_MFAMaskException();
                    }
                }
            }
        } else {
            // must never happen! but just in case, lets throw a login mask, that'll be just fine
            throw new SSO_Facade_SAML_LoginMaskException();
        }

        // we are authenticated now...
        // we maybe should add some data somewhere...
        $state['Attributes'] = [
            'eduPersonPrincipalName' => [$user->accountDisplayName],
            'uid' => [$user->accountLoginName],
            'firstName' => [$user->accountFirstName],
            'lastName' => [$user->accountLastName],
            'email' => [$user->accountEmailAddress],
        ];
    }
}
