<?php

use Jumbojett\OpenIDConnectClient;

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @todo allow to create user if it does not exist
 */
class Tinebase_Auth_OpenIdConnect extends Tinebase_Auth_Adapter_Abstract
{
    protected $_client = null;
    protected $_oidcResponse = null;
    protected $_userInfo = null;
    protected $_user = null;

    public function setOICDResponse($oidcResponse)
    {
        $this->_oidcResponse = $oidcResponse;
    }
    
    /**
     * Performs an authentication attempt
     *
     * @return Zend_Auth_Result
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function authenticate()
    {
        // TODO validate state / auth?

        parse_str($this->_oidcResponse, $responseArray);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($responseArray, true));

        if (! isset($responseArray['access_token'])) {
            throw new Tinebase_Exception_InvalidArgument('no access token in response string');
        }

        $oidc = $this->_getClient();
        $oidc->setAccessToken($responseArray['access_token']);
        $this->_userInfo = $oidc->requestUserInfo();

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_userInfo, true));

        $this->_user = $this->getLoginUser();

        if ($this->_user) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                $this->_user->accountLoginName
            );
        } else {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                $this->_userInfo->email
            );
        }
    }

    /**
     * send auth request to provider - request gets redirected to tine20 login page
     *
     * @return bool success
     * @throws Tinebase_Exception
     */
    public function providerAuthRequest()
    {
        $oidc = $this->_getClient();

        $ssoConfig = Tinebase_Config::getInstance()->{Tinebase_Config::SSO};

        $redirectUrl = $ssoConfig->{Tinebase_Config::SSO_REDIRECT_URL};
        if (empty($redirectUrl)) {
            $redirectUrl = Tinebase_Core::getUrl();
        }
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Set provider redirect url: ' . $redirectUrl);
        $oidc->setRedirectURL($redirectUrl);

        $oidc->addScope('openid email');
        $oidc->setResponseTypes(array('id_token', 'token'));
        $oidc->setAllowImplicitFlow(true);

        // TODO add this (if configured)
        //$oidc->setCertPath('/path/to/my.cert');

        try {
            $oidc->authenticate();
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Auth request successful.');
            return true;
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            return false;
        }
    }

    /**
     * @return null|Tinebase_Model_FullUser
     */
    public function getLoginUser()
    {
        if ($this->_user) {
            return $this->_user;
        }

        // depends on provider?
        if (! $this->_userInfo->email_verified) {
            return null;
        }
        try {
            // fetch user from DB (need to put user email address in user table)
            // always use email as ID?
            $user = Tinebase_User::getInstance()->getUserByProperty('openid', $this->_userInfo->email, Tinebase_Model_FullUser::class);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $user = null;
        }

        return $user;
    }

    protected function _getClient()
    {
        if ($this->_client === null) {
            $ssoConfig = Tinebase_Config::getInstance()->{Tinebase_Config::SSO};
            if (! $ssoConfig->{Tinebase_Config::SSO_ACTIVE}) {
                throw new Tinebase_Exception('sso client config inactive');
            }

            $provider_url = $ssoConfig->{Tinebase_Config::SSO_PROVIDER_URL};
            $client_id = $ssoConfig->{Tinebase_Config::SSO_CLIENT_ID};
            $client_secret = $ssoConfig->{Tinebase_Config::SSO_CLIENT_SECRET};

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Set provider url: ' . $provider_url);

            $this->_client = new OpenIDConnectClient($provider_url,
                $client_id,
                $client_secret);
        }

        return $this->_client;
    }
}
