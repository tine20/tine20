<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for addressbook, does event and container handling
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Admin
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor
     */
    private function __construct()
    {
        $this->_applicationName = 'Admin';
        $this->_defaultsSettings = array(
            Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK  => NULL,
        );
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller;
        }
        
        return self::$_instance;
    }
    
    /**
     * resolve some config settings
     * 
     * @param array $_settings
     */
    protected function _resolveConfigSettings($_settings)
    {
        foreach ($_settings as $key => $value) {
            if ($key === Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK && $value) {
                $_settings[$key] = Tinebase_Container::getInstance()->get($value)->toArray();
            }
        }
        
        return $_settings;
    }

    public static function addFastRoutes(\FastRoute\RouteCollector $r)
    {
        $r->addGroup('/admin', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->post('/ovpnapi/{apikey}/validate/check', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicPostOVpnApi', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }

    public function publicPostOVpnApi(string $apikey): \Psr\Http\Message\ResponseInterface
    {
        if (!($key = Admin_Config::getInstance()->{Admin_Config::OVPN_API}->{Admin_Config::OVPN_API_KEY}) ||
                $apikey !== $key) {
            return $this->_publicPostOVpnApiReturnError('bad request, api key wrong');
        }
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        if (!($body = json_decode($request->getBody()->getContents(), true)) || !isset($body['user']) ||
                !isset($body['pass']) || !isset($body['realm'])) {
            return $this->_publicPostOVpnApiReturnError('bad request, json body needs to have user, pass and realm');
        }

        /** @var Admin_Model_OVpnApiAccount $account */
        $account = Admin_Controller_OVpnApiAccount::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Admin_Model_OVpnApiAccount::class, [
                ['field' => Admin_Model_OVpnApiAccount::FLD_NAME, 'operator' => 'equals', 'value' => $body['user']],
                ['field' => Admin_Model_OVpnApiAccount::FLD_REALM, 'operator' => 'definedBy', 'value' => [
                    ['field' => Admin_Model_OVpnApiRealm::FLD_KEY, 'operator' => 'equals', 'value' => $body['realm']],
                ]],
                ['field' => Admin_Model_OVpnApiAccount::FLD_IS_ACTIVE, 'operator' => 'equals', 'value' => true],
            ]))->getFirstRecord();

        if (null === $account) {
            return $this->_publicPostOVpnApiReturnStatus(0);
        }
        if ($account->{Admin_Model_OVpnApiAccount::FLD_PIN}) {
            if (strpos($body['pass'], $account->{Admin_Model_OVpnApiAccount::FLD_PIN}) !== 0) {
                return $this->_publicPostOVpnApiReturnStatus(0);
            }
            $body['pass'] = substr($body['pass'], strlen($account->{Admin_Model_OVpnApiAccount::FLD_PIN}));
        }

        foreach ($account->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS}
                     ->filter(Admin_Model_OVpnApi_AuthConfig::FLD_IS_ACTIVE, true) as $authConfig) {
            $mfa = Tinebase_Auth_MFA::getInstance($authConfig->{Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID});
            $mfa->setPersistUserConfigDelegator(function(Closure $cb) use($account) {
                if (!$cb(new Tinebase_Model_FullUser([
                            'mfa_configs' => $account->{Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS}
                        ], true))) {
                    return false;
                }
                Admin_Controller_OVpnApiAccount::getInstance()->update($account);
                return true;
            });
            if ($mfa->validate($body['pass'], $authConfig)) {
                return $this->_publicPostOVpnApiReturnStatus(1);
            }
        }

        return $this->_publicPostOVpnApiReturnStatus(0);
    }

    protected function _publicPostOVpnApiReturnStatus($value): \Laminas\Diactoros\Response
    {
        return (new \Laminas\Diactoros\Response('php://memory', 200))
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(
                (new \Laminas\Diactoros\StreamFactory())->createStream(
                    json_encode(['result' => ['status' => ['value' => $value]]])
                )
            );
    }

    protected function _publicPostOVpnApiReturnError($msg): \Laminas\Diactoros\Response
    {
        return (new \Laminas\Diactoros\Response('php://memory', 200))
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(
                (new \Laminas\Diactoros\StreamFactory())->createStream(
                    json_encode(['result' => ['error' => ['message' => $msg]]])
                )
            );
    }
}
