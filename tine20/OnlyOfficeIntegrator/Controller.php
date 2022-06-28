<?php
/**
 * OnlyOfficeIntegrator Controller
 *
 * @package      OnlyOfficeIntegrator
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Cornelius Weiß <c.weiss@metaways.de>
 * @copyright    Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use \Psr\Http\Message\RequestInterface;
use \Firebase\JWT\JWT;

/**
 * OnlyOfficeIntegrator Controller
 *
 * @TODO
 * - forcesave all docs and drop all users when upgrading installation
 * - keep editing state (what if we miss a status update???)
 *  -> we can't query for editors so far - right?
 *  -> on documentservice restart all users are dropped right? - how to get this
 *   -> maybe just 'cache' state in Redis
 *    -> drop users on cache clear - or from time to time?
 *
 *
 * @package      OnlyOfficeIntegrator
 * @subpackage   Controller
 */
class OnlyOfficeIntegrator_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    const KEY_SEPARATOR = '.';

    protected $_cmdServiceClientAdapter = null;

    protected $_applicationName = OnlyOfficeIntegrator_Config::APP_NAME;

    public static function addFastRoutes(
        /** @noinspection PhpUnusedParameterInspection */
        \FastRoute\RouteCollector $r
    ) {
        $r->addGroup('/' . OnlyOfficeIntegrator_Config::APP_NAME, function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/getDocument/{token}', (new Tinebase_Expressive_RouteHandler(
                self::class, 'getDocument', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/getChanges/{token}[/{revision}]', (new Tinebase_Expressive_RouteHandler(
                self::class, 'getChanges', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/updateStatus/{token}', (new Tinebase_Expressive_RouteHandler(
                self::class, 'updateStatus', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }

    public function getChanges($token)
    {
        // WTF! getChanges is a jwt less CORS request from the browser
        // $this->_checkJwt();
        
        if (!is_string($token) || empty($token)) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('parameter token needs to be a string', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        if (null === $accessToken) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
        }

        if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} ===
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token is temp file, no history available', 404);
        }

        try {
            $node = Tinebase_FileSystem::getInstance()->stat(OnlyOfficeIntegrator_Controller::getRevisionsChangesPath()
                . '/' . $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID} . '/' .
                $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION});
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Tinebase_Exception_Expressive_HttpStatus('changes not found', 404);
        }
        $stream = new \Zend\Diactoros\Stream(Tinebase_FileSystem::getInstance()->getRealPathForHash($node->hash));
        $name = $node->name;
        $contentType = $node->contenttype;

        $raii->release();

        return new \Zend\Diactoros\Response($stream, 200, [
            'Content-Disposition'   => 'attachment; filename="' . $name . '"',
            'Content-Type'          => $contentType,
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function getDocument($token)
    {
        $this->_checkJwt();

        if (!is_string($token) || empty($token)) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('parameter token needs to be a string', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }
        $raii = Tinebase_RAII::getTransactionManagerRAII();

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        if (null === $accessToken) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
        }

        if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} ===
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
            try {
                /** @var Tinebase_Model_TempFile $tempFile */
                $tempFile = Tinebase_TempFile::getInstance()
                    ->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Tinebase_Exception_Expressive_HttpStatus('node not found', 404);
            }
            $stream = new \Zend\Diactoros\Stream($tempFile->path);
            $name = $tempFile->name;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_file($finfo, $tempFile->path);
            finfo_close($finfo);
        } else {
            try {
                $node = Tinebase_FileSystem::getInstance()->get(
                    $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, false,
                    $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION});
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Tinebase_Exception_Expressive_HttpStatus('node not found', 404);
            }
            $stream = new \Zend\Diactoros\Stream(Tinebase_FileSystem::getInstance()->getRealPathForHash($node->hash));
            $name = $node->name;
            $contentType = $node->contenttype;
        }

        $raii->release();


        return new \Zend\Diactoros\Response($stream, 200, [
            'Content-Disposition'   => 'attachment; filename="' . $name . '"',
            'Content-Type'          => $contentType,
        ]);
    }

    /**
     * check JWT for correct payload URL
     *
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkJwt()
    {
        /** @var \Zend\Diactoros\Request $request **/
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $jwt = $this->_getDecodedAuthToken($request, false);
        
        if (!isset($jwt['payload']['url'])) {
            throw new Tinebase_Exception_AccessDenied('jwt mismatch: url missing from payload');
        } else {
            $requestUri = preg_replace('/\?.*$/', '', $request->getUri());
            if ($jwt['payload']['url'] !== $requestUri) {
                throw new Tinebase_Exception_AccessDenied('jwt mismatch (maybe tine20Url confing is missing?): '
                    . $jwt['payload']['url'] . ' !== ' . $requestUri);
            }
        }
    }
    
    public function updateStatus($token)
    {
        if (!is_string($token) || empty($token)) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('parameter token needs to be a string', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }

        /** @var \Zend\Diactoros\Request $request */
        $request = Tinebase_Core::getContainer()->get(RequestInterface::class);
        $conf = OnlyOfficeIntegrator_Config::getInstance();
        // header auth signing check
        $this->_getDecodedAuthToken($request);
        $requestData = json_decode((string)$request->getBody(), true);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' requestData ' . print_r($requestData, true));
        }

        if (!isset($requestData['status']) || !isset($requestData['key'])) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('bad message format, status or key not set', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }

        // even in Maintenance Mode we still need to update document status, so that we can actually enter the full
        // maintenance mode state (all documents closed / all tokens resolved)
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->doRightChecks(false);

        try {
            switch ((int)$requestData['status']) {
                // case 2: ready for saving, do so, invalidate token
                case 2:
                    return $this->processStatus2($requestData, $token);

                //case 3: document save error, treat like 4 -> invalidate token
                case 3:
                    $e = new Tinebase_Exception_Backend('OnlyOffice send status 3: save error');
                    $e->setLogToSentry(true);
                    $e->setLogLevelMethod('warn');
                    Tinebase_Exception::log($e);
                // case 4: document closed without changes -> invalidate token
                case 4:
                    return $this->processStatus4($token);

                // case 6: force save, just save, no token invalidation
                case 6:
                    return $this->processStatus6($requestData, $token);

                default:
                    $response = new \Zend\Diactoros\Response();
                    $response->getBody()->write('{
    "error": 0
}');
                    return $response;
            }
        } catch (Throwable $t) {
            if ((2 === (int)$requestData['status'] || 6 === (int)$requestData['status']) && isset($requestData['url'])) {
                // eventually rewrite the source url depending on config
                if ($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} && $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL}) {
                    $requestData['url'] = str_replace($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL},
                        $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL}, $requestData['url']);
                }

                if (!Tinebase_Core::getUser()) {
                    Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()
                        ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
                }

                // figure out target path ... also file ending rewrite happens here
                $srcEnding = ltrim(substr($requestData['url'], strrpos($requestData['url'], '.')), '.');

                $path = Tinebase_Model_Tree_Node_Path::createFromRealPath('/shared/OOIQuarantine',
                    Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));
                $trgtPath = $path->statpath . '/' . date('c') . '_' . uniqid() . $srcEnding;

                if (!($srcStream = @fopen($requestData['url'], 'r'))) {
                    $e = new Tinebase_Exception_Backend('could not open document to save');
                    Tinebase_Exception::log($e);
                    throw $t;
                }
                $closeSrcStreamRaii = new Tinebase_RAII(function() use(&$srcStream) {
                    @fclose($srcStream);
                });
                if (!($dstStream = @fopen('tine20://' . $trgtPath, 'w'))) {
                    $e = new Tinebase_Exception_Backend('could not open tine node for writing');
                    Tinebase_Exception::log($e);
                    throw $t;
                }
                if (false === @stream_copy_to_stream($srcStream, $dstStream)) {
                    $e = new Tinebase_Exception_Backend('stream copy failed');
                    Tinebase_Exception::log($e);
                    throw $t;
                }
                if (false === @fclose($dstStream)) {
                    $e = new Tinebase_Exception_Backend('tine20 fclose failed');
                    Tinebase_Exception::log($e);
                    throw $t;
                }
                // just for unused variable check
                unset($closeSrcStreamRaii);

                $node = Tinebase_FileSystem::getInstance()->stat($trgtPath);
                $node->description = 'token: ' . $token . PHP_EOL . 'request_data: ' . print_r($requestData, true) .
                    PHP_EOL . $t->getMessage() . PHP_EOL . $t->getTraceAsString();
                $node = Tinebase_FileSystem::getInstance()->update($node);

                try {
                    $token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            OnlyOfficeIntegrator_Model_AccessToken::class, [
                                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN,
                                    'operator' => 'equals', 'value' => $token],
                                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED,
                                    'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
                    ]))->getFirstRecord();
                    if ($token) {
                        $node->description = $node->description . PHP_EOL . print_r($token->toArray(false), true);
                        $node = Tinebase_FileSystem::getInstance()->update($node);
                        $path = Tinebase_FileSystem::getInstance()->getPathOfNode(
                            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, true);
                        $node->description = $path . PHP_EOL . $node->description;
                        Tinebase_FileSystem::getInstance()->update($node);
                    }
                } catch (Throwable $tt) {}
            }
            throw $t;
        }
    }

    protected function processStatus6($requestData, $token)
    {
        if (!isset($requestData['url'])) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('bad message format, url not set', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        $allTokens = $this->doSave($requestData, $token);
        $previousRevision = null;

        $allTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE} = Tinebase_DateTime::now();
        if ((int)$allTokens->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} !==
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
            $node = Tinebase_FileSystem::getInstance()->get($allTokens->getFirstRecord()
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            $previousRevision = $allTokens->getFirstRecord()
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION};
            $allTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} = $node->revision;
        }
        foreach ($allTokens as $token) {
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($token);
        }

        $raii->release();

        $this->createHistory($allTokens->getFirstRecord(), $requestData, $previousRevision);

        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write('{
    "error": 0
}');
        return $response;
    }

    protected function processStatus4($token)
    {
        $raii = Tinebase_RAII::getTransactionManagerRAII();
        $allTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]));

        foreach ($allTokens as $tokenRecord) {
            $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} = 1;
            $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION} = 4;

            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($tokenRecord);
        }

        $raii->release();


        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write('{
    "error": 0
}');
        return $response;
    }

    protected function processStatus2($requestData, $token)
    {
        if (!isset($requestData['url'])) {
            $e = new Tinebase_Exception_Expressive_HttpStatus('bad message format, url not set', 400);
            $e->setLogToSentry(true);
            $e->setLogLevelMethod('notice');
            throw $e;
        }

        $raii = Tinebase_RAII::getTransactionManagerRAII();

        $allTokens = $this->doSave($requestData, $token);

        $previousRevision = null;
        if ((int)$allTokens->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} !==
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
            $node = Tinebase_FileSystem::getInstance()->get($allTokens->getFirstRecord()
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
            $previousRevision = $allTokens->getFirstRecord()
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION};
        } else {
            $node = null;
        }

        $tokenRecord = null;
        foreach ($allTokens as $tokenRecord) {
            if (null !== $node) {
                $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} = $node->revision;
            }
            $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} = 1;
            $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION} = 2;
            $tokenRecord->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE} = Tinebase_DateTime::now();

            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($tokenRecord);
        }

        $raii->release();

        if ($tokenRecord) {
            $this->createHistory($tokenRecord, $requestData, $previousRevision);
        }

        $response = new \Zend\Diactoros\Response();
        $response->getBody()->write('{
    "error": 0
}');
        return $response;
    }

    protected function createHistory(OnlyOfficeIntegrator_Model_AccessToken $accessToken, array $requestData, $previousRevision)
    {
        if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} !==
            (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {

            if (isset($requestData['changesurl']) && isset($requestData['history'])) {

                $raii = Tinebase_RAII::getTransactionManagerRAII();

                // eventually rewrite the source url depending on config
                $conf = OnlyOfficeIntegrator_Config::getInstance();
                if ($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} && $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL}) {
                    $requestData['changesurl'] = str_replace($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL},
                        $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL}, $requestData['changesurl']);
                }
                
                // download changesurl
                if (!($srcStream = fopen($requestData['changesurl'], 'r'))) {
                    throw new Tinebase_Exception_Backend('could not open changes url');
                }
                $closeSrcStreamRaii = new Tinebase_RAII(function () use (&$srcStream) {
                    @fclose($srcStream);
                });

                Tinebase_FileSystem::getInstance()->mkdir(static::getRevisionsChangesPath() . '/' . $accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
                if (!($dstStream = fopen('tine20://' . static::getRevisionsChangesPath() . '/' . $accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID} . '/' . $accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION}, 'w'))) {
                    throw new Tinebase_Exception_Backend('could not open tine node to store revisions changes');
                }

                if (false === stream_copy_to_stream($srcStream, $dstStream)) {
                    throw new Tinebase_Exception_Backend('stream copy failed');
                }
                if (false === fclose($dstStream)) {
                    throw new Tinebase_Exception_Backend('tine20 fclose failed');
                }
                // just for unused variable check
                unset($closeSrcStreamRaii);

                // save history meta data
                $user = Tinebase_Core::getUser();
                OnlyOfficeIntegrator_Controller_History::getInstance()->create(new OnlyOfficeIntegrator_Model_History([
                    OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID => $accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID},
                    OnlyOfficeIntegrator_Model_History::FLDS_NODE_REVISION => $accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION},
                    OnlyOfficeIntegrator_Model_History::FLDS_VERSION => $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION},
                    OnlyOfficeIntegrator_Model_History::FLDS_JSON => [
                        'history' => $requestData['history'],
                        'created' => Tinebase_DateTime::now()->toString(),
                        'user' => [
                            'id' => $user->getId(),
                            'name' => $user->getTitle(),
                        ],
                    ],
                ]));

                $raii->release();
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                    __LINE__ . 'changesurl or history key missing, no history created for current save!' . PHP_EOL .
                    print_r($requestData, true));
            }
        }
    }

    protected function doSave($requestData, $token)
    {
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        // lets check if we are allowed to write, meaning if at anytime a person with write rights was on the token
        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = ($allTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]), new Tinebase_Model_Pagination([
            'order' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS,
            'dir'   => 'DESC'
        ])))->getFirstRecord();

        if (null === $accessToken || $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS} <
                OnlyOfficeIntegrator_Model_AccessToken::GRANT_WRITE) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
        }

        if ($requestData['key'] !== $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY}) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token doesnt match key', 400);
        }

        $conf = OnlyOfficeIntegrator_Config::getInstance();

        $saveConflict = false;
        // also check if the token itself is still alive (even if the writing person is not alive anymore)
        if ($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} &&
            (int)OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->searchCount(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'equals', 'value' => $token],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => 0],
                ])) === 0) {

            $timeLimit = Tinebase_DateTime::now()->subDay(1);
            if ($timeLimit->isLater($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN})) {
                throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
            }

            // if there was no token created in the meantime, we allow the save anyway, otherwise we save a conflict
            if (OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->searchCount(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                        ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN, 'operator' => 'not',
                            'value' => $token],
                        ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID, 'operator' => 'equals',
                            'value' => $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}],
                        ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN, 'operator' => 'after',
                            'value' => $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN}],
                    ])) > 0) {
                // save conflict!
                $saveConflict = true;
            }
        }

        // eventually rewrite the source url depending on config
        if ($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} && $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL}) {
            $requestData['url'] = str_replace($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL},
                $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL}, $requestData['url']);
        }

        Tinebase_Core::setUser($user = Tinebase_User::getInstance()->getFullUserById($accessToken
            ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID}));

        // figure out target path ... also file ending rewrite happens here
        $srcEnding = ltrim(substr($requestData['url'], strrpos($requestData['url'], '.')), '.');
        $oldName = null;
        $newName = '';
        $tempFile = null;
        try {
            if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} ===
                    (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
                /** @var Tinebase_Model_TempFile $tempFile */
                $tempFile = Tinebase_TempFile::getInstance()->get($accessToken
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
                $trgtPath = $tempFile->path;

                $trgtEnding = ltrim(substr($tempFile->name, $pos = strrpos($tempFile->name, '.')), '.');
                if ($trgtEnding !== $srcEnding) {
                    if (false !== $pos) {
                        $tempFile->name = substr($tempFile->name, 0, $pos);
                    }
                    $tempFile->name = $tempFile->name . '.' . $srcEnding;
                }
            } else {
                $trgtPath = 'tine20://' . Tinebase_FileSystem::getInstance()->getPathOfNode($accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, true);

                $trgtEnding = ltrim(substr($trgtPath, $pos = strrpos($trgtPath, '.')), '.');
                if ($trgtEnding !== $srcEnding || $saveConflict) {
                    if (false === $pos) {
                        $trgtStart = $trgtPath;
                    } else {
                        $trgtStart = substr($trgtPath, 0, $pos);
                    }

                    $tmpPath = $trgtStart . '.' . ($saveConflict ? 'conflict.' : '') . $srcEnding;
                    $i = 1;
                    while (file_exists($tmpPath)) {
                        $tmpPath = $trgtStart . ' (' . $i . ').' . ($saveConflict ? 'conflict.' : '') . $srcEnding;
                        if (++$i > 100) {
                            throw new Tinebase_Exception_Backend('there are already 100 copies of this file');
                        }
                    }
                    $trgtPath = $tmpPath;
                    $node = Tinebase_FileSystem::getInstance()->get($accessToken
                        ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
                    $oldName = $node->name;
                    $array = explode('/', $trgtPath);
                    $newName = $node->name = @end($array);
                    /** do not use the return of this call! revision will be increased later! */
                    Tinebase_FileSystem::getInstance()->update($node);
                    Tinebase_FileSystem::getInstance()->clearStatCache();
                }
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Tinebase_Exception_Expressive_HttpStatus('node not found', 404);
        }

        if (!($srcStream = fopen($requestData['url'], 'r'))) {
            throw new Tinebase_Exception_Backend('could not open document to save');
        }
        $closeSrcStreamRaii = new Tinebase_RAII(function() use(&$srcStream) {
            @fclose($srcStream);
        });


        if (!($dstStream = fopen($trgtPath, 'w'))) {
            throw new Tinebase_Exception_Backend('could not open tine node for writing');
        }

        if (false === stream_copy_to_stream($srcStream, $dstStream)) {
            throw new Tinebase_Exception_Backend('stream copy failed');
        }
        if (false === fclose($dstStream)) {
            throw new Tinebase_Exception_Backend('tine20 fclose failed');
        }
        // just for unused variable check
        unset($closeSrcStreamRaii);

        if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} !==
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {

            $notes = Tinebase_Notes::getInstance()->searchNotes(new Tinebase_Model_NoteFilter([
                [
                    'field' => 'record_id',
                    'operator' => 'equals',
                    'value' =>
                        $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}
                ],
                ['field' => 'record_model', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_Node::class],
            ]), new Tinebase_Model_Pagination([
                'sort' => 'seq',
                'dir' => 'DESC',
                'limit' => 2,
            ]));

            $note = $notes->getFirstRecord();

            if (null === $note || $note->created_by !== $user->getId()) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' did not find file modification note');
            } else {
                if ($oldName !== null) {
                    $newNote = ' name (' . $oldName . ' -> ' . $newName . ')';
                    $note->note = $note->note . $newNote;
                    if ($notes->count() === 2 && strpos($notes->getLastRecord()->note, $newNote) !== false) {
                        $notes->removeFirst();
                        Tinebase_Notes::getInstance()->deleteNotes($notes);
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' did not find file rename note');
                    }
                }
                $allUsers = join(', ', Tinebase_User::getInstance()->getMultiple($allTokens
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID}, Tinebase_Model_FullUser::class)
                    ->accountDisplayName);

                $note->note = str_replace($user->accountDisplayName, $allUsers, $note->note);
                Tinebase_Notes::getInstance()->update($note);
            }
        } else if ($tempFile) {
            $tempFile->size = filesize($trgtPath);
            Tinebase_TempFile::getInstance()->update($tempFile);
        }

        return $allTokens;
    }

    /**
     * returns decoded auth token signed by OO
     *
     * @param RequestInterface $request
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getDecodedAuthToken(RequestInterface $request, $checkBody = true)
    {
        $conf = OnlyOfficeIntegrator_Config::getInstance();
        $auth = $request->getHeader('Authorization');
        if (! isset($auth[0]) || ! preg_match('/^Bearer (.+)/', $auth[0], $token)) {
            throw new Tinebase_Exception_AccessDenied('auth token missing');
        }

        $key = $conf->get(OnlyOfficeIntegrator_Config::JWT_SECRET);
        try {
            JWT::$leeway = 10;
            $decoded = json_decode(json_encode(JWT::decode($token[1], $key, array('HS256'))), true);
        } catch (Exception $e) {
            throw new Tinebase_Exception_AccessDenied('auth token not valid: ' . $e->getMessage());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' JWT data ' . print_r($decoded, true));

        if ($checkBody) {
            $bodyMsg = json_decode((string)$request->getBody(), true);

            // OODS > v7 sends token in body
            if (!is_array($decoded) || !isset($decoded['payload']) || $decoded['payload'] !== array_diff_key($bodyMsg, ['token' => $token[1]])) {
                throw new Tinebase_Exception_AccessDenied('auth token doesn\'t match body');
            }
        }

        return $decoded;
    }

    public function setCmdServiceClientAdapter(Zend_Http_Client_Adapter_Interface $adapter = null)
    {
        $this->_cmdServiceClientAdapter = $adapter;
    }

    protected function _getCmdServiceHttpClient($url)
    {
        $client = new Zend_Http_Client($url);
        if ($this->_cmdServiceClientAdapter) {
            $client->setAdapter($this->_cmdServiceClientAdapter);
        }

        return $client;
    }

    public function callCmdServiceForceSave(OnlyOfficeIntegrator_Model_AccessToken $token)
    {
        if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL})) {
            if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
                    ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL})) {
                throw new Tinebase_Exception_Backend('no only office url configured');
            }
        }

        $url = rtrim($url, '/') . '/coauthoring/CommandService.ashx';
        $response = $this->_getCmdServiceHttpClient($url)->setRawData($body = json_encode([
            'c'         => 'forcesave',
            'key'       => $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY},
            'userdata'  => Tinebase_Record_Abstract::generateUID(),
        ]))->setHeaders('Authorization', 'Bearer ' . JWT::encode(['payload' => $body],
                OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->request(Zend_Http_Client::POST);

        if (200 !== $response->getStatus()) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $response->getStatus() . ' ' .
                $response->getMessage() . PHP_EOL . $response->getBody());
            throw new Tinebase_Exception_Backend('onlyoffice command service did not responde with status code 200');
        }
        if (!is_array($body = json_decode($response->getBody(), true)) || !isset($body['error'])) {
            throw new Tinebase_Exception_Backend('onlyoffice command service response body not well formed: ' .
                $response->getBody());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . " onlyoffice command service response:\n"  . $response->getHeadersAsString() . "\n" . $response->getBody());

        // TODO we probably need to return more information what happend here...
        if ((int)$body['error'] === 0 || (int)$body['error'] === 4) {
            return true;
        }

        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' onlyoffice command service response: ' .
            $response->getBody());

        return false;
    }

    public function callCmdServiceDrop($documentKey, $documentUsers)
    {
        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL})) {
            if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL})) {
                throw new Tinebase_Exception_Backend('no only office url configured');
            }
        }

        $url = rtrim($url, '/') . '/coauthoring/CommandService.ashx';
        $response = $this->_getCmdServiceHttpClient($url)->setRawData($body = json_encode([
            'c'     => 'drop',
            'key'   => $documentKey,
            'users' => $documentUsers,
        ]))->setHeaders('Authorization', 'Bearer ' . JWT::encode(['payload' => $body],
                OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->request(Zend_Http_Client::POST);

        if (200 !== $response->getStatus()) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $response->getStatus() . ' ' .
                $response->getMessage() . PHP_EOL . $response->getBody());
            throw new Tinebase_Exception_Backend('onlyoffice command service did not responde with status code 200');
        }
        if (!is_array($body = json_decode($response->getBody(), true)) || !isset($body['error'])) {
            throw new Tinebase_Exception_Backend('onlyoffice command service response body not well formed: ' .
                $response->getBody());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . " onlyoffice command service response:\n"  . $response->getHeadersAsString() . "\n" . $response->getBody());

        // TODO we probably need to return more information what happend here...
        if ((int)$body['error'] === 0 || (int)$body['error'] === 1) {
            return true;
        }

        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' onlyoffice command service response: ' .
            $response->getBody());

        return false;
    }

    public function isDocumentOpenInOOServer(OnlyOfficeIntegrator_Model_AccessToken $token): bool
    {
        return $this->callCmdServiceInfo($token)['error'] !== 1;
    }

    public function callConversionService(array $requestData): array
    {
        if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL})) {
            if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL})) {
                throw new Tinebase_Exception_Backend('no only office url configured');
            }
        }

        $url = rtrim($url, '/') . '/ConvertService.ashx';

        $response = $this->_getCmdServiceHttpClient($url)->setRawData($body = json_encode($requestData))
            ->setHeaders('Authorization', 'Bearer ' . JWT::encode(['payload' => $body],
                OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->setHeaders('Accept', 'application/json')
            ->request(Zend_Http_Client::POST);

        if (200 !== $response->getStatus()) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $response->getStatus() . ' ' .
                $response->getMessage() . PHP_EOL . $response->getBody());
            throw new Tinebase_Exception_Backend('onlyoffice conversion service did not responde with status code 200');
        }
        if (!is_array($body = json_decode($response->getBody(), true))) {
            throw new Tinebase_Exception_Backend('onlyoffice conversion service response body not well formed: ' .
                $response->getBody());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . " onlyoffice conversion service response:\n"  . $response->getHeadersAsString() . "\n" . $response->getBody());

        return $body;
    }

    public function callCmdServiceInfo(OnlyOfficeIntegrator_Model_AccessToken $token): array
    {
        if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL})) {
            if (empty($url = OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL})) {
                throw new Tinebase_Exception_Backend('no only office url configured');
            }
        }

        $url = rtrim($url, '/') . '/coauthoring/CommandService.ashx';
        $response = $this->_getCmdServiceHttpClient($url)->setRawData($body = json_encode([
            'c'     => 'info',
            'key'   => $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY},
        ]))->setHeaders('Authorization', 'Bearer ' . JWT::encode(['payload' => $body],
                OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256'))
            ->request(Zend_Http_Client::POST);

        if (200 !== $response->getStatus()) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $response->getStatus() . ' ' .
                $response->getMessage() . PHP_EOL . $response->getBody());
            throw new Tinebase_Exception_Backend('onlyoffice command service did not responde with status code 200');
        }
        if (!is_array($body = json_decode($response->getBody(), true)) || !isset($body['error'])) {
            throw new Tinebase_Exception_Backend('onlyoffice command service response body not well formed: ' .
                $response->getBody());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . " onlyoffice command service response:\n"  . $response->getHeadersAsString() . "\n" . $response->getBody());

        if (!in_array($body['error'],[0,1])) {
            throw new Tinebase_Exception_Backend('onlyoffice cmd service returns the unacceptable error code' .
                $response->getBody());
        }

        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' onlyoffice command service response: ' .
            $response->getBody());

        return $body;
    }

    public static function getNewTemplatePath()
    {
        return Tinebase_FileSystem::getInstance()
            ->getApplicationBasePath(OnlyOfficeIntegrator_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED) .
            '/newTemplates';
    }

    public static function getRevisionsChangesPath()
    {
        return Tinebase_FileSystem::getInstance()
                ->getApplicationBasePath(OnlyOfficeIntegrator_Config::APP_NAME,
                Tinebase_FileSystem::FOLDER_TYPE_RECORDS) . '/revisionChanges';
    }

    public function goIntoMaintenanceMode()
    {
        $raii = Tinebase_RAII::getTransactionManagerRAII();

        parent::goIntoMaintenanceMode();

        $accessTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search();
        $keys = array_unique($accessTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY});

        foreach ($keys as $key) {
            $users = array_unique($accessTokens->filter(OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, $key)
                ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID});
            $this->callCmdServiceDrop($key, $users);
            $this->callCmdServiceForceSave($accessTokens->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, $key));
        }

        $raii->release();
    }

    public function isInMaintenanceMode()
    {
        if (parent::isInMaintenanceMode()) {
            $ttl = Tinebase_DateTime::now()->subSecond(OnlyOfficeIntegrator_Config::getInstance()
                ->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME});

            $tokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN, 'operator' => 'after_or_equals',
                        'value' => $ttl],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION, 'operator' => 'equals',
                        'value' => 0],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals',
                        'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET]
                ]));
            $keys = [];
            foreach ($tokens as $token) {
                if (!isset($keys[$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY}])) {
                    $keys[$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY}] = $token;
                    if ($this->isDocumentOpenInOOServer($token)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function isGoingIntoOrInMaintenanceMode()
    {
        return parent::isInMaintenanceMode();
    }
}
