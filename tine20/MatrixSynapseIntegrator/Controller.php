<?php

/**
 * MatrixSynapseIntegrator Controller
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use \Psr\Http\Message\RequestInterface;

/**
 * MatrixSynapseIntegrator Controller
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Controller
 */
class MatrixSynapseIntegrator_Controller extends Tinebase_Controller_Event
{
    private function __construct()
    {
        $this->_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;
    }

    private function __clone()
    {
    }

    private static $_instance = null;

    /**
     * singleton
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function addFastRoutes(
        /** @noinspection PhpUnusedParameterInspection */
        \FastRoute\RouteCollector $r
    ) {
        $r->post('/_matrix-internal/identity/v1/check_credentials', (new Tinebase_Expressive_RouteHandler(
            self::class, 'checkCredentials', [
            Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
        ]))->toArray());
        $r->post('/_matrix-internal/identity/v1/directory', (new Tinebase_Expressive_RouteHandler(
            self::class, 'directory'
        ))->toArray());
        $r->post('/_matrix-internal/identity/v1/identity', (new Tinebase_Expressive_RouteHandler(
            self::class, 'identity'
        ))->toArray());
        $r->post('/_matrix-internal/identity/v1/profile', (new Tinebase_Expressive_RouteHandler(
            self::class, 'profile'
        ))->toArray());
    }

    public function profile()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '...');

        /** @var \Zend\Diactoros\Request $request **/
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $bodyMsg = json_decode((string)$request->getBody(), true);

        if (!is_array($bodyMsg) || !isset($bodyMsg['mxid']) || !isset($bodyMsg['localpart']) || !isset($bodyMsg['domain'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' illegal body: ' . (string)$request->getBody());
            throw new Tinebase_Exception_Expressive_HttpStatus('illegal or missing json body', 400);
        }

        $result = [
            'profile' => $this->_getProfileForUser(
                Tinebase_User::getInstance()->getFullUserByLoginName($bodyMsg['localpart'])
            )
        ];

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' response: ' . print_r($result, true));

        $response = (new \Zend\Diactoros\Response())->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function identity()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '...');

        /** @var \Zend\Diactoros\Request $request **/
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $bodyMsg = json_decode((string)$request->getBody(), true);

        if (!is_array($bodyMsg) || !isset($bodyMsg['lookup']) || !is_array($bodyMsg['lookup']) ||
            ((!isset($bodyMsg['lookup']['medium']) || !isset($bodyMsg['lookup']['address'])) &&
                (!isset($bodyMsg['lookup'][0]) || !is_array($bodyMsg['lookup'][0]) ||
                    !isset($bodyMsg['lookup'][0]['medium']) || !isset($bodyMsg['lookup'][0]['address']) ))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' illegal body: ' . (string)$request->getBody());
            throw new Tinebase_Exception_Expressive_HttpStatus('illegal or missing json body', 400);
        }

        $result = ['lookup' => []];
        if (isset($bodyMsg['lookup']['medium'])) {
            $lookups = [$bodyMsg['lookup']];
        } else {
            $lookups = $bodyMsg['lookup'];
        }

        $emails = [];
        foreach ($lookups as $lookup) {
            if ('email' === $lookup['medium']) {
                $emails[] = $lookup['address'];
            }
        }
        if ($emails) {
            /** @var Tinebase_Model_FullUser $user */
            foreach (Tinebase_User::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Tinebase_Model_FullUser::class, [
                        ['field' => 'email', 'operator' => 'in', 'value' => $emails]
                    ])) as $user) {
                $result['lookup'][] = [
                    'medium' => 'email',
                    'address' => $user->accountEmailAddress,
                    'id' => [
                        'type' => 'localpart',
                        'value' => $user->accountLoginName,
                    ]
                ];
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' response: ' . print_r($result, true));

        $response = (new \Zend\Diactoros\Response())->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode(isset($bodyMsg['lookup']['medium']) ?
            (isset($result['lookup'][0]) ? $result['lookup'][0] : []) : $result));

        return $response;
    }

    public function directory()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '...');

        /** @var \Zend\Diactoros\Request $request **/
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $bodyMsg = json_decode((string)$request->getBody(), true);

        if (!is_array($bodyMsg) || !isset($bodyMsg['by']) || !in_array($bodyMsg['by'], ['name', 'threepid']) ||
                !isset($bodyMsg['search_term'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' illegal body: ' . (string)$request->getBody());
            throw new Tinebase_Exception_Expressive_HttpStatus('illegal or missing json body', 400);
        }

        $filter = [];
        if ('name' === $bodyMsg['by']) {
            $filter[] = [
                'field' => 'display_name', 'operator' => 'contains', 'value' => $bodyMsg['search_term']
            ];
        } else {
            $filter[] = [
                'field' => 'email', 'operator' => 'contains', 'value' => $bodyMsg['search_term']
            ];
        }

        $result = [
            'limited' => false,
            'results' => []
        ];

        /** @var Tinebase_Model_FullUser $user */
        foreach (Tinebase_User::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Tinebase_Model_FullUser::class, $filter)) as $user) {
            $result['results'][] = [
                'display_name'  => $user->accountDisplayName,
                'user_id'       => $user->accountLoginName,
            ];
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' response: ' . print_r($result, true));

        $response = (new \Zend\Diactoros\Response())->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));

        return $response;
    }

    public function checkCredentials()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '...');

        /** @var \Zend\Diactoros\Request $request **/
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $bodyMsg = json_decode((string)$request->getBody(), true);

        if (!is_array($bodyMsg) || !isset($bodyMsg['user']['id']) || !isset($bodyMsg['user']['password'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' illegal body: ' . (string)$request->getBody());
            throw new Tinebase_Exception_Expressive_HttpStatus('illegal or missing json body', 400);
        }

        $matrixId = $bodyMsg['user']['id'];
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' matrix id: ' . $matrixId);

        $domain = $this->getMatrixDomain();

        $result = ['auth' => ['success' => false]];
        if (preg_match('/^@(.*):' . preg_quote($domain, '/') . '$/', $matrixId, $matches)) {
            $username = $matches[1];

            $authResult = Tinebase_Auth::getInstance()->authenticate($username, $bodyMsg['user']['password']);
            if ($authResult->getCode() === Tinebase_Auth::SUCCESS && $user = Tinebase_User::getInstance()->getFullUserByLoginName(
                    $authResult->getIdentity())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' auth succeeded');

                // needed for acl checks
                Tinebase_Core::set(Tinebase_Core::USER, $user);
                $result['auth']['success'] = true;
                $result['auth']['mxid'] = $matrixId;
                $result['auth']['profile'] = $this->_getProfileForUser($user);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' response: ' . print_r($result, true));

        $response = (new \Zend\Diactoros\Response())->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($result));

        return $response;
    }

    protected function _getProfileForUser(Tinebase_Model_FullUser $user): array
    {
        $result = [
            'display_name'  => $user->accountDisplayName,
            'three_pids'    => [
                [
                    'medium'    => 'email',
                    'address'   => $user->accountEmailAddress,
                ]
            ]
        ];

        /** @var Addressbook_Model_Contact $contact */
        $contact = Addressbook_Controller_Contact::getInstance()->get($user->contact_id);
        if (null !== ($msisdn = $contact->getMsisdn())) {
            $result['three_pids'][] = [
                'medium'    => 'msisdn',
                'address'   => $msisdn
            ];
        }

        return $result;
    }

    public function getMatrixDomain()
    {
        return MatrixSynapseIntegrator_Config::getInstance()->{MatrixSynapseIntegrator_Config::MATRIX_DOMAIN} ?:
            Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST);
    }
}
