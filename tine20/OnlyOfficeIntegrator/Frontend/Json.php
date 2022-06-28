<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use \Firebase\JWT\JWT;

/**
 * OnlyOfficeIntegrator json fronend
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 *
 */
class OnlyOfficeIntegrator_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected static $_fileExtensions = [
        'text' => [
        '.doc', '.docm', '.docx', '.dot', '.dotm', '.dotx', '.epub', '.fodt', '.htm', '.html', '.mht', '.odt', '.ott',
        '.pdf', '.rtf', '.txt', '.djvu', '.xps'],
        'spreadsheet' => ['.csv', '.fods', '.ods', '.ots', '.xls', '.xlsm', '.xlsx', '.xlt', '.xltm', '.xltx'],
        'presentation' => ['.fodp', '.odp', '.otp', '.pot', '.potm', '.potx', '.pps', '.ppsm', '.ppsx', '.ppt', '.pptm', '.pptx'],
    ];

    protected static $_imageFileExtensions = [
        'image' => ['.jpg', '.jpeg', '.gif', '.png', '.tiff'],
    ];

    protected static $_newTypeMapping = [
        'text' => 'new.docx',
        'spreadsheet' => 'new.xlsx',
        'presentation' => 'new.pptx',
    ];

    protected static $_newTypeFilename = [
        'text' => 'New Document', // _('New Document')
        'spreadsheet' => 'New Spreadsheet', // _('New Spreadsheet')
        'presentation' => 'New Presentation', // _('New Presentation')
    ];

    protected static $_newTypeAltFilename = [
        'text' => 'New Document (%1$d)', // _('New Document (%1$d)')
        'spreadsheet' => 'New Spreadsheet (%1$d)', // _('New Spreadsheet (%1$d)')
        'presentation' => 'New Presentation (%1$d)', // _('New Presentation (%1$d)')
    ];

    protected static $_altFilenameTemplate = ' (%1$d)';

    const PATH_TYPE_TEMPFILE = 'tempFile';
    const PATH_TYPE_PERSONAL = 'personal';

    public function getHistoryData($_key, $_version)
    {
        if (!is_string($_key) || empty($_key)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter _key needs to be a none empty string');
        }
        $_version = (int)$_version;
        if ($_version < 1) {
            throw new Tinebase_Exception_UnexpectedValue('parameter _version needs to be an integer greater 0');
        }

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $transRaii = (new Tinebase_RAII(function () use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' => $_key],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID, 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]))->getFirstRecord();

        if (null === $accessToken) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
        }

        $fs = Tinebase_FileSystem::getInstance();
        $node = $fs->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, false, $_version);
        if ($fs->isFile(OnlyOfficeIntegrator_Controller::getRevisionsChangesPath() . '/' . $node->getId() . '/' .
                $node->revision)) {
            $changesFileAvailable = true;
        } else {
            $changesFileAvailable = false;
        }

        $grants = new Tinebase_Model_Grants([], true);
        $revisionToken = $this->getTokenForNode($node, $grants, static::$_fileExtensions,
            OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY);
        if (($prevRevision = $node->getPreviousRevision()) > 0) {
            $nodePrevRevision = $fs->get($node->getId(), false, $prevRevision);
            $prevRevisionToken = $this->getTokenForNode($nodePrevRevision, $grants, static::$_fileExtensions,
                OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY);
            $previous = [
                'key'           => $prevRevisionToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY},
                'url'           => $prevRevisionToken->getEditorConfig()['document']['url'],
            ];
        } else {
            $previous = null;
        }
        
        $transRaii->release();

        $historyData = [
            'changesUrl'    => rtrim(Tinebase_Core::getUrl(), '/') . '/OnlyOfficeIntegrator/getChanges/' . $revisionToken
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN} . '/' . $node->revision,
            'key'           => $revisionToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY},
            'previous'      => $previous,
            'url'           => $revisionToken->getEditorConfig()['document']['url'],
            'version'       => $node->revision,
        ];
        if (!$changesFileAvailable) {
            unset($historyData['changesUrl']);
        }
        if (null === $previous) {
            unset($historyData['previous']);
        }
        $historyData['token'] = JWT::encode($historyData, OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256');

        return $historyData;
    }

    public function getHistory($_key)
    {
        if (!is_string($_key) || empty($_key)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter _key needs to be a none empty string');
        }

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $transRaii = (new Tinebase_RAII(function () use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' => $_key],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID, 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ]))->getFirstRecord();

        if (null === $accessToken) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not valid', 404);
        }

        $node = Tinebase_FileSystem::getInstance()->get($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID});
        $histories = OnlyOfficeIntegrator_Controller_History::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_History::class, [
                ['field' => OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID, 'operator' => 'equals', 'value' => $node->getId()],
            ]), new Tinebase_Model_Pagination(['sort' => OnlyOfficeIntegrator_Model_History::FLDS_VERSION, 'dir' => 'ASC']));

        $transRaii->release();

        $result = [];
        if (is_array($node->available_revisions) && count($node->available_revisions) > 0) {
            $available = $node->available_revisions;
            sort($available, SORT_NUMERIC);
            $hists = $histories->asArray();
            reset($hists);

            $notes = Tinebase_Notes::getInstance()->searchNotes(new Tinebase_Model_NoteFilter([
                ['field' => 'record_model', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_Node::class],
                ['field' => 'record_id',    'operator' => 'equals', 'value' => $node->getId()],
            ]));

            $resolvUserFunc = function($userId) {
                static $userCache = [];
                if (!isset($userCache[$userId])) {
                    try {
                        $user = Tinebase_User::getInstance()->getUserById($userId);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $user = new Tinebase_Model_FullUser([
                            'accountDisplayName' => $userId,
                        ], true);
                    }
                    $userCache[$userId] = $user;
                }
                return $userCache[$userId]->accountDisplayName;
            };

            foreach ($available as $availRev) {
                $availRev = (int)$availRev;
                $h = current($hists);

                while (false !== $h && $availRev > (int)$h->{OnlyOfficeIntegrator_Model_History::FLDS_VERSION}) {
                    $h = next($hists);
                }

                if (false === $h || $availRev < (int)$h->{OnlyOfficeIntegrator_Model_History::FLDS_VERSION}) {
                    $note = $notes->find('note', '/ revision \\(' . ($availRev - 1) . ' -> ' . $availRev . '\\)/', true);
                    if (null === $note) {
                        $note = new Tinebase_Model_Note(['created_by' => 'xxx', 'creation_time' => '1970-01-03 00:00:00'], true);
                    }

                    $result[] = new OnlyOfficeIntegrator_Model_History([
                        OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID        => $node->getId(),
                        OnlyOfficeIntegrator_Model_History::FLDS_NODE_REVISION  => $availRev,
                        OnlyOfficeIntegrator_Model_History::FLDS_VERSION        => $availRev,
                        OnlyOfficeIntegrator_Model_History::FLDS_JSON           => [
                            'history'       => [
                                'changes'       => [],
                                'serverVersion' => 0,
                            ],
                            'created' => $note->creation_time,
                            'user' => [
                                'id'    => $note->created_by,
                                'name'  => $resolvUserFunc($note->created_by),
                            ],
                        ]
                    ]);
                } else {
                    $result[] = $h;
                    next($hists);
                }
            }
        }
        $histories = new Tinebase_Record_RecordSet(OnlyOfficeIntegrator_Model_History::class, $result);


        $grants = new Tinebase_Model_Grants([], true);
        $result = [
            'currentVersion' => $node->revision,
            'history' => [],
        ];

        /** @var OnlyOfficeIntegrator_Model_History $history */
        foreach($histories as $history) {
            if (null === $history->getId()) {
                $revisionToken = $this->getTokenForNode(Tinebase_FileSystem::getInstance()->get(
                    $history->{OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID}, false,
                    $history->{OnlyOfficeIntegrator_Model_History::FLDS_NODE_REVISION}), $grants,
                    static::$_fileExtensions, OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY);
            } else {
                $revisionToken = null;
            }

            $result['history'][] = $history->getRefreshHistoryData($revisionToken);
        }

        return $result;
    }

    public function createNew($_type, $targetPath, $_fileName = null)
    {
        if (!is_string($_type) || empty($_type) || !isset(static::$_newTypeMapping[$_type])) {
            throw new Tinebase_Exception_UnexpectedValue('parameter type needs to be a none empty string');
        }
        $type = static::$_newTypeMapping[$_type];

        if (!is_string($targetPath) || empty($targetPath)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter targetPath needs to be a none empty string');
        }

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $transRaii = (new Tinebase_RAII(function () use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        $fs = Tinebase_FileSystem::getInstance();
        $newTemplateFilePath = OnlyOfficeIntegrator_Controller::getNewTemplatePath() . '/' . $type;
        if (!$fs->isFile($newTemplateFilePath)) {
            throw new Tinebase_Exception_NotFound('no new template file for type ' . $_type . ' found');
        }

        if (!($srcStream = @fopen('tine20://' . $newTemplateFilePath, 'r'))) {
            throw new Tinebase_Exception_Backend('could not open new template file for reading');
        }
        $closeSrcStreamRaii = (new Tinebase_RAII(function() use(&$srcStream) {
            if (null !== $srcStream) {
                @fclose($srcStream);
            }
        }))->setReleaseFunc(function() use(&$srcStream) {
            @fclose($srcStream);
            $srcStream = null;
        });


        $translation = Tinebase_Translation::getTranslation(OnlyOfficeIntegrator_Config::APP_NAME);

        if (null !== $_fileName) {
            $newName = $_fileName;
        } else {
            $newName = $translation->_(static::$_newTypeFilename[$_type]);
        }

        $ending = substr($type, strrpos($type, '.'));


        if (self::PATH_TYPE_TEMPFILE === $targetPath) {
            $tmpPath = Tinebase_TempFile::getTempPath();
            if (!($dstStream = @fopen($tmpPath, 'w'))) {
                throw new Tinebase_Exception_Backend('could not open target path for writing');
            }
            $closeDstStreamRaii = (new Tinebase_RAII(function() use(&$dstStream, $tmpPath) {
                if (null !== $dstStream) {
                    @fclose($dstStream);
                    @unlink($tmpPath);
                }
            }))->setReleaseFunc(function() use(&$dstStream) {
                @fclose($dstStream);
                $dstStream = null;
            });

            stream_copy_to_stream($srcStream, $dstStream);
            $closeDstStreamRaii->release();
            $closeSrcStreamRaii->release();

            $result = Tinebase_TempFile::getInstance()->createTempFile($tmpPath, $newName . $ending,
                @mime_content_type($tmpPath));

        } else {
            if (self::PATH_TYPE_PERSONAL === $targetPath) {
                $userId = Tinebase_Core::getUser()->getId();
                $personalNode = $fs->getPersonalContainer($userId, Filemanager_Model_Node::class, $userId)->getFirstRecord();
                $targetPath = $fs->getPathOfNode($personalNode, true);
            } else {
                $targetPath = Tinebase_Model_Tree_Node_Path::createFromPath(
                    Filemanager_Controller_Node::getInstance()->addBasePath($targetPath))->statpath;
            }

            if (!$fs->isDir($targetPath)) {
                throw new Tinebase_Exception_NotFound('target path is not a folder');
            }

            if (Tinebase_Model_Tree_Node_Path::createFromPath($targetPath)->isToplevelPath()) {
                throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
            }

            $parentNode = $fs->stat($targetPath);
            $grants = $fs->getGrantsOfAccount(Tinebase_Core::getUser(), $parentNode);
            if (!$grants->{Tinebase_Model_Grants::GRANT_ADD}) {
                throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
            }

            $i = 0;
            while ($fs->isFile($targetPath . '/' . $newName . $ending)) {
                if (null !== $_fileName) {
                    $newName = $_fileName . sprintf(static::$_altFilenameTemplate, ++$i);
                } else {
                    $newName = sprintf($translation->_(static::$_newTypeAltFilename[$_type]), ++$i);
                }
            }
            $targetPath .= '/' . $newName . $ending;

            if (!($dstStream = @fopen('tine20://' . $targetPath, 'w'))) {
                throw new Tinebase_Exception_Backend('could not open target path for writing');
            }
            $closeDstStreamRaii = (new Tinebase_RAII(function() use(&$dstStream) {
                if (null !== $dstStream) {
                    @fclose($dstStream);
                }
            }))->setReleaseFunc(function() use(&$dstStream) {
                @fclose($dstStream);
                $dstStream = null;
            });

            // it may change here ... efile says hello
            if (isset(($streamOpts = stream_context_get_options(
                    Tinebase_FileSystem_StreamWrapper::getStream('tine20://' . $targetPath)))['tine20']['path'])) {
                $targetPath = $streamOpts['tine20']['path'];
            }

            stream_copy_to_stream($srcStream, $dstStream);
            $closeDstStreamRaii->release();
            $closeSrcStreamRaii->release();

            $result = $fs->stat($targetPath);
        }
        $transRaii->release();

        return $this->_recordToJson($result);
    }

    public function exportAs($ooUrl, $targetPath, $forceOverwrite = false)
    {
        if (!is_string($ooUrl) || empty($ooUrl)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter ooUrl needs to be a none empty string');
        }

        if (!is_string($targetPath) || empty($targetPath)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter targetPath needs to be a none empty string');
        }

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $transRaii = (new Tinebase_RAII(function () use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        $targetPath = Tinebase_Model_Tree_Node_Path::createFromPath(Filemanager_Controller_Node::getInstance()
            ->addBasePath($targetPath))->statpath;
        if (Tinebase_Model_Tree_Node_Path::createFromPath(dirname($targetPath))->isToplevelPath()) {
            throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
        }

        $fs = Tinebase_FileSystem::getInstance();
        if (!$forceOverwrite && $fs->fileExists($targetPath)) {
            throw new Tinebase_Exception_AccessDenied('file already exists');
        }
        $parentNode = $fs->stat(dirname($targetPath));
        $grants = $fs->getGrantsOfAccount(Tinebase_Core::getUser(), $parentNode);
        if (!$grants->{Tinebase_Model_Grants::GRANT_ADD}) {
            throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
        }

        $conf = OnlyOfficeIntegrator_Config::getInstance();
        if ($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL} && $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL}) {
            $ooUrl = str_replace($conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_PUBLIC_URL},
                $conf->{OnlyOfficeIntegrator_Config::ONLYOFFICE_SERVER_URL}, $ooUrl);
        }
        if (!($srcStream = @fopen($ooUrl, 'r'))) {
            throw new Tinebase_Exception_Backend('could not open document to export');
        }
        $closeSrcStreamRaii = new Tinebase_RAII(function() use(&$srcStream) {
            @fclose($srcStream);
        });

        if (!($dstStream = @fopen('tine20://' . $targetPath, $forceOverwrite ? 'w' : 'x'))) {
            throw new Tinebase_Exception_Backend('could not open target path for writing');
        }
        $closeDstStreamRaii = new Tinebase_RAII(function() use(&$dstStream) {
            if (null !== $dstStream) {
                @fclose($dstStream);
            }
        });

        if (false === stream_copy_to_stream($srcStream, $dstStream)) {
            throw new Tinebase_Exception_Backend('stream copy failed');
        }

        // it may change here ... efile says hello
        if (isset(($streamOpts = stream_context_get_options(
                Tinebase_FileSystem_StreamWrapper::getStream('tine20://' . $targetPath)))['tine20']['path'])) {
            $targetPath = $streamOpts['tine20']['path'];
        }

        if (false === fclose($dstStream)) {
            throw new Tinebase_Exception_Backend('tine20 fclose failed');
        }
        $dstStream = null;

        $resultNode = Tinebase_FileSystem::getInstance()->stat($targetPath);

        $transRaii->release();

        // just for unused variable check
        unset($closeSrcStreamRaii);
        unset($closeDstStreamRaii);

        return $this->_recordToJson($resultNode);
    }

    public function saveAs($key, $copyOrMove, $targetPath)
    {
        if (!is_string($key) || empty($key)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter key needs to be an none empty string');
        }

        if (!is_string($copyOrMove) || ($copyOrMove !== 'copy' && $copyOrMove !== 'move')) {
            throw new Tinebase_Exception_UnexpectedValue('parameter copyOrMove needs to be either "copy" or "move"');
        }

        if (!is_string($targetPath) || empty($targetPath)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter targetPath needs to be an none empty string');
        }

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $raii = (new Tinebase_RAII(function () use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        if (null === ($accessToken = ($allTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    [
                        'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY,
                        'operator' => 'equals',
                        'value' => $key
                    ],
                    [
                        'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED,
                        'operator' => 'equals',
                        'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET
                    ],
                ])))->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID,
                    Tinebase_Core::getSessionId(false))) ||
                $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED}) {
            throw new Tinebase_Exception_Expressive_HttpStatus('token not found', 404);
        }

        if ($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS} <
                OnlyOfficeIntegrator_Model_AccessToken::GRANT_WRITE) {
            throw new Tinebase_Exception_AccessDenied('you do not have grants to save');
        }

        $targetPath = Tinebase_Model_Tree_Node_Path::createFromPath(Filemanager_Controller_Node::getInstance()
            ->addBasePath($targetPath))->statpath;
        if (Tinebase_Model_Tree_Node_Path::createFromPath(dirname($targetPath))->isToplevelPath()) {
            throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
        }

        $fs = Tinebase_FileSystem::getInstance();
        $parentNode = $fs->stat(dirname($targetPath));
        $grants = $fs->getGrantsOfAccount(Tinebase_Core::getUser(), $parentNode);
        if (!$grants->{Tinebase_Model_Grants::GRANT_ADD}) {
            throw new Tinebase_Exception_AccessDenied('you don\'t have permissions to create a file there');
        }


        if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} ===
                (int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION) {
            if ($copyOrMove === 'move') {
                throw new Tinebase_Exception_UnexpectedValue('can\'t move a tempFile');
            }

            $newNode = $fs->copyTempfile($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID},
                $targetPath);
        } else {
            if ($copyOrMove === 'move') {
                $newNode = $fs->rename($fs->getPathOfNode($accessToken
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, true), $targetPath);
            } else {
                $newNode = $fs->copy($fs->getPathOfNode($accessToken
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}, true), $targetPath);
            }

            $allTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} = $newNode->revision;
        }

        $allTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID} = $newNode->getId();
        $allTokens->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TITLE} = $newNode->name;

        foreach ($allTokens as $token) {
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($token);
        }

        if (!OnlyOfficeIntegrator_Controller::getInstance()->callCmdServiceForceSave($accessToken)) {
            throw new Tinebase_Exception_Backend('only office cmd service failed');
        }

        $raii->release();

        return $this->_recordToJson($newNode);
    }

    public function tokenSignOut($key)
    {
        if (!is_string($key) || empty($key)) {
            throw new Tinebase_Exception_UnexpectedValue('parameter key needs to be a none empty string');
        }

        $backend = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->getBackend();
        $transRaii = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($backend);

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        // unsigned the session from the token
        if (null === ($token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    [
                        'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY,
                        'operator' => 'equals',
                        'value' => $key
                    ],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
                    [
                        'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID,
                        'operator' => 'equals',
                        'value' => Tinebase_Core::getSessionId(false)
                    ],
                ]))->getFirstRecord())) {
            throw new Tinebase_Exception_Expressive_HttpStatus('key not found', 404);
        }

        if ((int)$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} !== 1) {
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED} = 1;
            $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN} = Tinebase_DateTime::now();
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($token);
        }

        $transRaii->release();
        unset($selectForUpdateRaii);

        return true;
    }

    /**
     * @param array $keys
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     */
    public function tokenKeepAlive($keys)
    {
        if (!is_array($keys) || empty($keys = array_filter($keys, function($val) {return !empty($val);}))) {
            throw new Tinebase_Exception_UnexpectedValue('parameter keys needs to be an array of tokens');
        }

        $backend = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->getBackend();
        $transRaii = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdateRaii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($backend);

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        $accessTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'in', 'value' => $keys],
                ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID, 'operator' => 'equals',
                    'value' => Tinebase_Core::getSessionId(false)],
            ]));

        $remainingKeys = $keys;
        foreach ($accessTokens as $accessToken) {
            unset($remainingKeys[array_search($accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY}, $keys,
                true)]);
            $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN} = Tinebase_DateTime::now();
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($accessToken);
        }

        $reactivatedTokens = null;
        if (!empty($remainingKeys)) {
            $remainingAccessTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'in', 'value' =>
                        $remainingKeys],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID, 'operator' => 'equals',
                        'value' => Tinebase_Core::getSessionId(false)],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals',
                        'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
                ]));
            unset($selectForUpdateRaii);
            $reactivatedTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()
                ->reactivateTokens($remainingAccessTokens);
        }

        $transRaii->release();
        unset($selectForUpdateRaii);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $accessTokens->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, $key) !== null ||
                ($reactivatedTokens &&
                    $reactivatedTokens->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, $key) !== null);
        }
        return $result;
    }

    public function getEmbedUrlForNodeId($nodeId, $revision = null)
    {
        if (!is_string($nodeId) || empty($nodeId) || ($revision !== null && (!is_string($revision) || empty($revision)))) {
            throw new Tinebase_Exception_UnexpectedValue(
                'parameter nodeId needs to be string and revision either null or string');
        }

        $token = $this->getTokenForNodeId($nodeId, null, $revision, static::$_imageFileExtensions,
            OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY);

        $config = [
            'url' => $token->getEditorConfig()['document']['url'],
        ];

        $config['token'] = JWT::encode($config, OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256');

        return $config;
    }

    public function getEditorConfigForAttachment($model, $recordId, $attachmentNodeId)
    {
        if (!is_string($model) || empty($model) || !is_string($recordId) || empty($recordId) ||
                !is_string($attachmentNodeId) || empty($attachmentNodeId)) {
            throw new Tinebase_Exception_UnexpectedValue(
                'parameters model, recordId and attachmentNodeId need to be a string');
        }

        $ctrl = Tinebase_Core::getApplicationInstance(current(explode('_', $model)), $model);
        $record = $ctrl->get($recordId);

        // check nodeid is an attachement!
        $path = Tinebase_FileSystem::getInstance()->getPathOfNode($attachmentNodeId, true);
        $basePath = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentBasePath($record);
        if (strpos($path, $basePath) !== 0) {
            throw new Exception('bla');
        }

        $editGrant = false;
        try {
            $ctrl->public_checkRight(Tinebase_Controller_Record_Abstract::ACTION_UPDATE);
            $ctrl->checkGrant($record, Tinebase_Controller_Record_Abstract::ACTION_UPDATE, true, 'No Permission.', $record);

            $editGrant = true;
        } catch (Tinebase_Exception $e) {}

        $grants = new Tinebase_Model_Grants(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_DOWNLOAD => true,
            Tinebase_Model_Grants::GRANT_ADD => false,
            Tinebase_Model_Grants::GRANT_EDIT => $editGrant,
            Tinebase_Model_Grants::GRANT_DELETE => false,
            Tinebase_Model_Grants::GRANT_EXPORT => false,
            Tinebase_Model_Grants::GRANT_SYNC => false,
        ));

        return $this->_getEditorConfig($attachmentNodeId, $grants, null);
    }

    public function getEditorConfigForTempFileId($tempFileId, $fileName=null)
    {
        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $raii = (new Tinebase_RAII(function() use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        /** @var Tinebase_Model_TempFile $tempFile */
        $tempFile = Tinebase_TempFile::getInstance()->get($tempFileId);
        if ($tempFile->session_id !== Tinebase_Core::getSessionId(false)) {
            throw new Tinebase_Exception_AccessDenied('session id mismatch');
        }

        $node = new Tinebase_Model_Tree_Node([
            'id'            => $tempFile->getId(),
            'revision'      => OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION,
            'name'          => $fileName ? $fileName : $tempFile->name,
        ], true);

        try {
            $token = $this->getTokenForNode($node, new Tinebase_Model_Grants([
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_DOWNLOAD => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
            ], true), static::$_fileExtensions);
        } catch (OnlyOfficeIntegrator_Exception_WaitForOOSave $e) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
            do {
                usleep(300000);
            } while ($this->waitForTokenRequired($node->getId(), null, 30));
            if ($this->waitForTokenRequired($node->getId())) {
                throw new OnlyOfficeIntegrator_Exception_WaitForOOSave();
            }

            return $this->getEditorConfigForTempFileId($tempFileId);
        }

        $raii->release();

        $editorConfig = $token->getEditorConfig();

        $editorConfig['token'] = JWT::encode($editorConfig, OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256');

        return $editorConfig;
    }

    /**
     * get signed editor config
     * it is only possible to open one specific revision of a node at a time
     *
     * @param string $nodeId
     * @param string $revision
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function getEditorConfigForNodeId($nodeId, $revision = null)
    {
        if (!is_string($nodeId) || empty($nodeId) || ($revision !== null && (!is_string($revision) || empty($revision)))) {
            throw new Tinebase_Exception_UnexpectedValue(
                'parameter nodeId needs to be string and revision either null or string');
        }

        return $this->_getEditorConfig($nodeId, null, $revision);
    }

    protected function waitForTokenRequired($nodeId, $key = null, $timeOut = null, &$tokenToWaitFor = null)
    {
        if (null === $timeOut) {
            $timeOut = OnlyOfficeIntegrator_Config::getInstance()->{OnlyOfficeIntegrator_Config::TOKEN_LIVE_TIME};
        }
        return null !== ($tokenToWaitFor = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                [
                    'field' => $key ? OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY :
                        OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID,
                    'operator' => 'equals',
                    'value' => $key ?: $nodeId,
                ], [
                    'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED,
                    'operator' => 'equals',
                    'value' => 1,
                ], [
                    'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN,
                    'operator' => 'after',
                    'value' => Tinebase_DateTime::now()->subSecond($timeOut),
                ], [
                    'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION,
                    'operator' => 'equals',
                    'value' => 0,
                ], [
                    'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_MODE,
                    'operator' => 'equals',
                    'value' => OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_WRITE,
                ]
            ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord());
    }

    protected function getTokenForNode(Tinebase_Model_Tree_Node $node, $usersGrants, $allowedExtensions, $mode = OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_WRITE)
    {
        $fileType = null;
        $extension = null;
        if ($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER &&
            false !== ($pos = strrpos($node->name, '.'))) {
            $extension = strtolower(substr($node->name, $pos));
            foreach ($allowedExtensions as $type => $extensions) {
                if (in_array($extension, $extensions)) {
                    $fileType = $type;
                    break;
                }
            }
        }
        if (null === $fileType) {
            throw new Tinebase_Exception_SystemGeneric('filetype of node is not supported');
        }
        if (empty($node->revision)) {
            throw new Tinebase_Exception_SystemGeneric('node has no revision, it might be a partial upload');
        }

        OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->invalidateTimeouts();

        $filter = [
            [
                'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID,
                'operator' => 'equals',
                'value' => $node->getId(),
            ], [
                'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_MODE,
                'operator' => 'equals',
                'value' => $mode,
            ],
        ];
        if (OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY === $mode) {
            $filter[] = [
                'field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION,
                'operator' => 'equals',
                'value' => $node->revision,
            ];
        }
        $accessTokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, $filter));

        if ($accessTokens->count() > 0) {
            /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
            $accessToken = $accessTokens->getFirstRecord();

            if ((int)$accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} !==
                    (int)$node->revision) {
                if (OnlyOfficeIntegrator_Controller::getInstance()->isDocumentOpenInOOServer($accessToken)) {
                    // in case we would allow to open two different revisions at the same time, make sure that only one is writeable, all others need to be RO
                    throw new Tinebase_Exception_SystemGeneric(
                        'this revision currently can\'t be opened as a different revision is already open', 647);
                }
            }

            $token = $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN};
            $lastSave = $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE};
            $lastSaveForced = $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE_FORCED};
        } else {
            $waitFor = null;
            if (OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_WRITE === $mode &&
                    $this->waitForTokenRequired($node->getId(), null, null, $waitFor) &&
                    OnlyOfficeIntegrator_Controller::getInstance()->isDocumentOpenInOOServer($waitFor)) {
                throw new OnlyOfficeIntegrator_Exception_WaitForOOSave();
            }

            $token = Tinebase_Record_Abstract::generateUID();
            $lastSave = Tinebase_DateTime::now();
            $lastSaveForced = Tinebase_DateTime::now();
        }

        /** @var OnlyOfficeIntegrator_Model_AccessToken $accessToken */
        $accessToken = $accessTokens->find(OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID,
            Tinebase_Core::getSessionId(false));


        if (null === $accessToken) {
            $accessToken = new OnlyOfficeIntegrator_Model_AccessToken([
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_DOCUMENT_TYPE  => $fileType,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_FILE_TYPE      => ltrim($extension, '.'),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN      => Tinebase_DateTime::now(),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID        => $node->getId(),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION  => $node->revision,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_SESSION_ID     => Tinebase_Core::getSessionId(false),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_USER_ID        => Tinebase_Core::getUser()->getId(),
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_TOKEN          => $token,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS         => $usersGrants
                    ->{Tinebase_Model_Grants::GRANT_EDIT} ?
                    OnlyOfficeIntegrator_Model_AccessToken::GRANT_WRITE :
                    OnlyOfficeIntegrator_Model_AccessToken::GRANT_READ,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY            => $accessTokens->count() > 0 ?
                    $accessTokens->getFirstRecord()->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY} :
                        Tinebase_Core::getTinebaseId() .
                        OnlyOfficeIntegrator_Controller::KEY_SEPARATOR . $node->getId() .
                        OnlyOfficeIntegrator_Controller::KEY_SEPARATOR . $node->revision .
                        OnlyOfficeIntegrator_Controller::KEY_SEPARATOR . $token,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_TITLE          => $node->name,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_MODE           => $mode,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE_FORCED => $lastSaveForced,
                OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SAVE      => $lastSave,
            ]);
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->create($accessToken);

        } else {
            $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_LAST_SEEN} = Tinebase_DateTime::now();
            $accessToken->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_GRANTS} = $usersGrants
                ->{Tinebase_Model_Grants::GRANT_EDIT} ? OnlyOfficeIntegrator_Model_AccessToken::GRANT_WRITE :
                OnlyOfficeIntegrator_Model_AccessToken::GRANT_READ;
            OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->update($accessToken);
        }

        return $accessToken;
    }

    protected function getTokenForNodeId($nodeId, $usersGrants, $revision, $allowedExtensions, $mode = OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_WRITE)
    {
        $fs = Tinebase_FileSystem::getInstance();

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        // if $transId is not set to null, rollback. note the & pass-by-ref! otherwise it would not work
        $raii = (new Tinebase_RAII(function() use (&$transId) {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }))->setReleaseFunc(function () use (&$transId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;
        });

        $node = $fs->get($nodeId, false, $revision);
        if (null === $usersGrants) {
            $usersGrants_ = $fs->getGrantsOfAccount(Tinebase_Core::getUser(), $node);
        } else {
            $usersGrants_ = $usersGrants;
        }

        if (!$usersGrants_->{Tinebase_Model_Grants::GRANT_READ}) {
            throw new Tinebase_Exception_AccessDenied('you do not have access to node ' . $nodeId);
        }

        if (OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY === (int)$mode ||
                (null !== $revision && $node->getHighestRevision() !== (int)$revision)) {
            $mode = OnlyOfficeIntegrator_Model_AccessToken::MODE_READ_ONLY;
            $usersGrants_->{Tinebase_Model_Grants::GRANT_EDIT} = false;
        }

        try {
            $accessToken = $this->getTokenForNode($node, $usersGrants_, $allowedExtensions, $mode);
        } catch (Tinebase_Exception_AreaLocked $teal) {
            if ($teal->getMessage() !== 'wait required') throw $teal;
            $raii->release();
            do {
                usleep(300000);
            } while ($this->waitForTokenRequired($nodeId, null, 30));
            if ($this->waitForTokenRequired($nodeId)) {
                throw new OnlyOfficeIntegrator_Exception_WaitForOOSave();
            }

            return $this->getTokenForNodeId($nodeId, $usersGrants, $revision, $allowedExtensions, $mode);
        }

        $raii->release();

        return $accessToken;
    }

    public function waitForDocumentSave($key, $timeout)
    {
        if (!is_string($key) || empty($key) || !is_numeric($timeout) || empty($timeout) || (int)$timeout < 1 ||
            (int)$timeout > 600) {
            throw new Tinebase_Exception_UnexpectedValue(
                'parameter key needs to be string and timeout numeric in range 1 - 600');
        }

        while ($this->waitForTokenRequired(null, $key, (int)$timeout)) usleep(300000);

        if (null !== ($token = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(OnlyOfficeIntegrator_Model_AccessToken::class, [
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_KEY, 'operator' => 'equals', 'value' => $key],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_INVALIDATED, 'operator' => 'equals', 'value' => 1],
                    ['field' => OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION, 'operator' => 'greater', 'value' => 0],
                ]), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord())) {
            if ((int)OnlyOfficeIntegrator_Model_AccessToken::TEMP_FILE_REVISION === (int)$token
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION}) {
                return $this->_recordToJson(Tinebase_TempFile::getInstance()->get($token
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}));
            } else {
                return $this->_recordToJson(Tinebase_FileSystem::getInstance()->get($token
                    ->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID}));
            }
        }

        return false;
    }

    protected function _getEditorConfig($nodeId, $userGrants, $revision)
    {
        $token = $this->getTokenForNodeId($nodeId, $userGrants, $revision, static::$_fileExtensions);

        $editorConfig = $token->getEditorConfig();

        $editorConfig['token'] = JWT::encode($editorConfig, OnlyOfficeIntegrator_Config::getInstance()
            ->{OnlyOfficeIntegrator_Config::JWT_SECRET}, 'HS256');

        return $editorConfig;
    }
}
