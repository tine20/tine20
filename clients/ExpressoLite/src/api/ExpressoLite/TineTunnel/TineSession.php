<?php
/**
 * Tine Tunnel
 * Represents an estabilished session between a user and Tine.
 * Provides facilities for login, caches useful login info (account, registry
 * data) and handles cookies.
 *
 * @package   ExpressoLite\TineTunnel
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\TineTunnel;

use ExpressoLite\TineTunnel\CookieHandler;
use ExpressoLite\TineTunnel\TineJsonRpc;
use ExpressoLite\Exception\PasswordExpiredException;
use ExpressoLite\Exception\CaptchaRequiredException;
use ExpressoLite\Exception\TineErrorException;
use ExpressoLite\Exception\TineSessionExpiredException;
use ExpressoLite\Exception\LiteException;

class TineSession implements CookieHandler
{

    /**
     * @var string $tineUrl Tine URL address
     */
    private $tineUrl;

    /**
     * @var string $jsonKey Tine's jsonKey estabilished during login
     */
    private $jsonKey = null;

    /**
     * @var array $cookies An indexed array with all cookie names and values
     * associated with the TineSession
     */
    private $cookies = array();

    /**
     * @var array $attributes An indexed array with revelant attribute names
     * and values associated to the current session. These are usually stored
     * during the initial login process.
     */
    private $attributes = array();

    /**
     * @var boolean $activateTineXDebug if this is true, it will append
     * GET parameters to the call that will activate XDebug in the Tine server.
     * Should be used only for debug purposes
     */
    private $activateTineXDebug = false;

    /**
     * @var string $locale Locale to be used in Tine
     */
    private $locale = null;

    /**
     * @var boolean $isLocaleSet Indicates whether this session has already set
     * Tine's locale with 'Tinebase.setLocale'
     */
    private $isLocaleSet = false;

    /**
     * Creates a new TineSession that will be associated to a target Tine url.
     *
     * @param $tineUrl The address in which Tine is located
     */
    public function __construct($tineUrl)
    {
        $this->tineUrl = $tineUrl;
    }

    /**
     * @return the URL of the Tine server to which this session is connected to
     */
    public function getTineUrl()
    {
        return $this->tineUrl;
    }

    /**
     * @return the JSON key this session got during login
     *
     */
    public function getJsonKey()
    {
        return $this->jsonKey;
    }

    /**
     * Sets Tine locale to be used for this session. This will
     * impact in the language of messages in future responses from Tine.
     *
     */
    public function setLocale($locale)
    {
        return $this->locale = $locale;
    }

    /**
     * Public method that executes a JSON RPC call to tine. If no locale
     * has been set for this session yet, it first sets Tine locale, so that
     * the response comes in the expected language.
     *
     * @param string $method The RPC method to be executed
     * @param array $params The params of the RPC
     * @param boolean $acceptErrors Indicates if exception throwing should
     *     be supreressed when Tine returns a response with an error
     *
     * @return The JSON RPC call response given by Tine
     */
    public function jsonRpc($method, $params = array(), $acceptErrors = false)
    {
        if ($this->locale !== null && ! $this->isLocaleSet) {
            $this->sendJsonRpc('Tinebase.setLocale', (object) array(
                'localeString' => $this->locale,
                'saveaspreference' => 'true',
                'setcookie' => 'true'
            ));
            $this->isLocaleSet = true;
        }

        try {
            return $this->sendJsonRpc($method, $params, $acceptErrors);
        } catch (TineErrorException $tee) {
            // We check to see if the error happened because of an expired session in Tine.
            // This is done here instead of in TineSession because it's only here we have
            // context enough to check for session expiration

            $error = $tee->getTineError();
            if (isset($error->code) && $error->code == -32000 && !$this->tineIsAuthenticated()) {
                throw new TineSessionExpiredException();
            } else {
                throw $tee; //its not because of an expired session, its something else
            }
        }
    }

    /**
     * Private method that executes a JSON RPC call to tine
     * (without worrying about locale).
     *
     * @param string $method The RPC method to be executed
     * @param array $params The params of the RPC
     * @param boolean $acceptErrors Indicates if exception throwing should
     *     be supreressed when Tine returns a response with an error
     *
     * @return The JSON RPC call response given by Tine
     */
    private function sendJsonRpc($method, $params = array(), $acceptErrors = false)
    {
        $tineJsonRpc = new TineJsonRpc();

        $tineJsonRpc->setTineUrl($this->tineUrl);
        $tineJsonRpc->setActivateTineXDebug($this->activateTineXDebug);
        $tineJsonRpc->setCookieHandler($this);
        $tineJsonRpc->setRpcMethod($method);
        $tineJsonRpc->setRpcParams($params);
        $tineJsonRpc->setJsonKey($this->jsonKey);
        $tineJsonRpc->setAcceptErrors($acceptErrors);
        return $tineJsonRpc->send();
    }


    /**
     * Executes Tinebase.login in Tine and returns its result.
     *
     * @param string $user User login
     * @param string $password User password
     *
     * @return The result of the login attempt as returned by Tine
     *
     */
    private function getLoginInfo($user, $password, $captcha = null)
    {
        try {
            return $this->jsonRpc('Tinebase.login', (object) array(
                'username' => $user,
                'password' => $password,
                'securitycode' => $captcha === null ? '' : $captcha
            ));
        } catch (Exception $e) {
            throw new LiteException('Tinebase.login failed: ' . $e->getMessage());
        }
    }

    /**
     * Executes Tinebase.getAllRegistryData in Tine and returns its result.
     *
     * @return The result of Tinebase.getAllRegistryData as returned by Tine
     *
     */
    private function getAllRegistryData()
    {
        return $this->jsonRpc('Tinebase.getAllRegistryData');
    }


    /**
     * Logins the tine session in Tine, registering the jsonKey to
     * perform authenticated calls in the future. It also caches relevant
     * attributes from login and registry data for future reference.
     *
     * @param string $user User login
     * @param string $password User password
     *
     * @return True if the login was successful, false otherwise
     *
     */
    public function login($user, $password, $captcha = null)
    {
        $loginInfo = $this->getLoginInfo($user, $password, $captcha);

        if ($loginInfo->result->success === false) {
            if (strpos($loginInfo->result->errorMessage, 'Your password has expired. You must change it.') === 0) {
                throw new PasswordExpiredException();
            } else if (isset($loginInfo->result->c1)) {
                throw new CaptchaRequiredException($loginInfo->result->c1);
            }
        }

        if ($loginInfo->result->success) {
            $registryData = $this->getAllRegistryData();

            $this->jsonKey = $loginInfo->result->jsonKey;
            $this->saveRelevantAttributes($loginInfo, $registryData);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Caches relevant values from login info and registry data
     * for quick reference in the future
     *
     * @param $loginInfo The result of Tinebase.login
     * @param $registryData The result of Tinebase.getAllRegistryData
     *
     */
    private function saveRelevantAttributes($loginInfo, $registryData)
    {
        $this->setAttribute('Expressomail.accountId', $registryData->result->Expressomail->accounts->results[0]->id);
        $this->setAttribute('Expressomail.email', $registryData->result->Expressomail->accounts->results[0]->email);
        $this->setAttribute('Tinebase.accountLoginName', $registryData->result->Tinebase->currentAccount->accountLoginName);
        $this->setAttribute('Tinebase.accountDisplayName', $registryData->result->Tinebase->currentAccount->accountDisplayName);
        $this->setAttribute('Expressomail.from', $registryData->result->Expressomail->accounts->results[0]->from);
        $this->setAttribute('Expressomail.organization', $registryData->result->Expressomail->accounts->results[0]->organization);
        $this->setAttribute('Expressomail.signature', $registryData->result->Expressomail->accounts->results[0]->signature);
        $this->setAttribute('Tinebase.accountId', $registryData->result->Tinebase->currentAccount->accountId);
        $this->setAttribute('Calendar.defaultEventColor', $registryData->result->Calendar->defaultContainer->color);
    }

    /**
     * @return true if this Tine session has already performed a successful
     * login in Tine, false otherwise. Note that this verification is done only
     * on Lite's side, without checking if Tine has somehow lost authentication
     * info. To do that, check TineSession::tineIsAuthenticated
     *
     */
    public function isLoggedIn()
    {
        return $this->jsonKey != null;
    }

    /**
     * Logs out from Tine
     *
     */
    public function logout()
    {
        try {
            $response = $this->jsonRpc('Tinebase.logout');
            $this->jsonKey = null;
        } catch (\Exception $e) {
            $this->jsonKey = null;
            // it is better to reset jsonKey to make this session to be considered
            // as not logged in even if something wrong happens during logout.
            // This way, the user will be able to login to a new session and
            // won't be stuck with a bogus tineSession

            throw new LiteException('Tinebase.logout: ' . $e->getMessage());
        }
    }

    /**
     * Stores a cookie that will be used for future requests to Tine.
     *
     * @param $cookie An object with two fields: $cookie->name and $cookie->value
     *
     */
    public function storeCookie($cookie)
    {
        $this->cookies[$cookie->name] = $cookie;
    }

    /**
     * Deletes a cookie from the cookie store
     *
     * @param string $cookieName Name of the cookie to be deleted.
     */
    public function deleteCookie($cookieName)
    {
        unset($this->cookies[$cookieName]);
    }

    /**
     * Replaces the value of an specific cookie
     *
     * @param string $cookieName Name of the cookie to be updated.
     * @param string $newValue The new value to be set for the cookie.
     */
    public function replaceCookieValue($cookieName, $newValue)
    {
        $this->cookies[$cookieName]->value = $newValue;
    }

    /**
     * Returns an array with all the stored cookies
     *
     * @return $cookie Array with all the stored cookies
     *
     */
    public function getCookies()
    {
        $result = array();
        foreach ($this->cookies as $name => $cookie) {
            $result[] = $cookie;
        }
        return $result;
    }

    /**
     * Returns the $cookie with a specific name
     *
     * @param string $cookieName The cookie name
     *
     * @return An object with two fields: $cookie->name and $cookie->value
     *
     */
    public function getCookie($cookieName)
    {
        return $this->cookies[$cookieName];
    }

    /**
     * Sets a generic attribute in this TineSession
     *
     * @param string $attrName The attribute name
     * @param string $attrValue The attribute value
     *
     */
    public function setAttribute($attrName, $attrValue)
    {
        $this->attributes[$attrName] = $attrValue;
    }

    /**
     * Gets a generic attribute value
     *
     * @param string $attrName The attribute name

     * @return The attribute value
     *
     */
    public function getAttribute($attrName)
    {
        return isset($this->attributes[$attrName]) ? $this->attributes[$attrName] : null;
    }

    /**
     * Sets $activateTineXDebug. When this attribute is true, all JSON RPC calls
     * made to Tine will activate XDebug in the Tine server
     * (if the server is configured for XDebug)
     *
     * @param booelan $activateTineXDebug

     */
    public function setActivateTineXDebug($activateTineXDebug)
    {
        $this->activateTineXDebug = $activateTineXDebug;
    }

    /**
     * Checks if our session with Tine is authenticated. To this, we check
     * if our registry data has a field name Tinebase->currentAccount
     *
     * @return boolean true if session is authenticated, false otherwise
     */
    public function tineIsAuthenticated() {
        try {
            $registryData = $this->sendJsonRpc('Tinebase.getAllRegistryData', array(), true);
            return isset($registryData->result->Tinebase) && isset($registryData->result->Tinebase->currentAccount);
        } catch (\Exception $e) {
            // Means we are having problem even to detect session authentication
            // We assume the session is lost
            return false;
        }
    }
}
