<?php declare(strict_types=1);

/**
 * AttachmentCache controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * AttachmentCache controller class for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_AttachmentCache extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Felamimail_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Felamimail_Model_AttachmentCache::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Felamimail_Model_AttachmentCache::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => false,
        ]);
        $this->_modelName = Felamimail_Model_AttachmentCache::class;
        $this->_purgeRecords = true;
        $this->_doContainerACLChecks = false;
    }

    public function checkTTL(): bool
    {
        $this->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_AttachmentCache::class, [
                ['field' => Felamimail_Model_AttachmentCache::FLD_TTL, 'operator' => 'before', 'value' => Tinebase_DateTime::now()],
        ]), new Tinebase_Model_Pagination([
            'limit' => 1000,
            'sort' => Felamimail_Model_AttachmentCache::FLD_TTL,
            'dir' => 'ASC',
            'start' => 0,
        ]));

        return true;
    }

    public function delete($_ids)
    {
        $raii = null;
        // we want the attachment to be hard deleted!
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            $fsConf = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
            $fsConf->unsetParent();
            $fsConf->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = false;
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $fsConf);
            $raii = new Tinebase_RAII(function() {
                Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                    ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
                Tinebase_FileSystem_RecordAttachments::destroyInstance();
                Tinebase_FileSystem::getInstance()->resetBackends();
            });
            Tinebase_FileSystem_RecordAttachments::destroyInstance();
            Tinebase_FileSystem::getInstance()->resetBackends();
        }
        try {
            return parent::delete($_ids);
        } finally {
            unset($raii);
        }
    }

    public function get($_id, $_containerId = null, $_getRelatedData = true, $_getDeleted = false, $_aclProtect = true)
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $selectForUpdate = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($this->_backend);
        $lock = null;

        try {
            /** @var Felamimail_Model_AttachmentCache $record */
            $record = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted, $_aclProtect);
            if ($record->isFSNode()) {
                $node = $this->getSourceRecord($record);
                if ($node->hash !== $record->{Felamimail_Model_AttachmentCache::FLD_HASH}) {
                    $this->delete($record);
                    throw new Tinebase_Exception_NotFound('hash out of sync, deleted cache, recreating...');
                }
            }
            $record->{Felamimail_Model_AttachmentCache::FLD_TTL} = Tinebase_DateTime::now()->addSecond(
                Felamimail_Config::getInstance()->{Felamimail_Config::ATTACHMENT_CACHE_TTL}
            );
            $this->getBackend()->update($record);

            return $record;
        } catch (Tinebase_Exception_NotFound $tenf) {
            $transaction->release(); // avoid deadlocks -> release here, create has its own transaction handling
            unset($selectForUpdate);

            /** @var Tinebase_Lock_Mysql $lock */
            $lock = Tinebase_Core::getMultiServerLock(__METHOD__ . $_id);
            if (!$lock->isLocked()) {
                while (false === $lock->tryAcquire(5)) {}
                return $this->get($_id);
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . ' creating cache for ' . $_id);
            $record = new Felamimail_Model_AttachmentCache(['id' => $_id, 'attachments' => []]);
            if (empty($record->{Felamimail_Model_AttachmentCache::FLD_SOURCE_ID})) {
                throw new Tinebase_Exception_NotFound('Could not find source record without ID');
            }
            return $this->create($record);

        } finally {
            if (null !== $lock && $lock->isLocked()) {
                $lock->release();
            }
            unset($selectForUpdate);
            $transaction->release();
        }
    }

    /**
     * @param Felamimail_Model_AttachmentCache $_record
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Mime_Exception
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $ctrl = Felamimail_Controller_Message::getInstance();

        $_record->{Felamimail_Model_AttachmentCache::FLD_TTL} = Tinebase_DateTime::now()->addSecond(
            Felamimail_Config::getInstance()->{Felamimail_Config::ATTACHMENT_CACHE_TTL}
        );

        if ($_record->isFSNode()) {
            $_record->{Felamimail_Model_AttachmentCache::FLD_HASH} = $this->getSourceRecord($_record)->hash;

            $msg = $ctrl->getMessageFromNode($_record->{Felamimail_Model_AttachmentCache::FLD_SOURCE_ID});
            $attachment = isset($msg['attachments'][$_record->{Felamimail_Model_AttachmentCache::FLD_PART_ID}]) ?
                $msg['attachments'][$_record->{Felamimail_Model_AttachmentCache::FLD_PART_ID}] : null;

            if ($attachment === null) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                    __LINE__ . ' Attachment not found');
                throw new Tinebase_Exception_UnexpectedValue('attachment not found for: ' .
                    print_r($_record->toArray(), true));
            }
            $stream = $attachment['contentstream']->detach();
            $fileName = $attachment['filename'];

        } else {
            try {
                $msgPart = $ctrl->getMessagePart($_record->{Felamimail_Model_AttachmentCache::FLD_SOURCE_ID},
                    $_record->{Felamimail_Model_AttachmentCache::FLD_PART_ID});
            } catch (Felamimail_Exception_IMAPMessageNotFound $feiamnf) {
                throw new Tinebase_Exception_NotFound($feiamnf->getMessage());
            }
            $stream = $msgPart->getDecodedStream();
            $fileName = $msgPart->filename;
        }

        if ($stream) {
            rewind($stream);
        } else if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
            // TODO throw exception?
            Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' No valid stream');
        }

        $name = $this->_sanitizeFilename($fileName);

        $_record->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [
            new Tinebase_Model_Tree_Node([
                'name' => $name,
                'tempFile' => true,
                'stream' => $stream,
            ], true)
        ]);
    }

    /**
     * @param mixed $filename
     * @return string
     */
    protected function _sanitizeFilename($filename): string
    {
        $fallback = 'unknown_file_name_' . uniqid();
        if (!$filename) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . ' No usable filename available');
            return $fallback;
        }

        if (! mb_detect_encoding($filename) || false === ($name = iconv(mb_detect_encoding($filename), "UTF-8//IGNORE", $filename))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . ' No usable filename available: ' . $filename);
            return $fallback;
        }

        return $name;
    }

    /**
     * @param Felamimail_Model_AttachmentCache $_record
     * @param string $_action
     * @param bool $_throw
     * @param string $_errorMessage
     * @param null $_oldRecord
     * @return bool
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if (self::ACTION_UPDATE === $_action) {
            throw new Tinebase_Exception_AccessDenied(Felamimail_Model_AttachmentCache::class . ' may not be updated');
        }
        try {
            $this->getSourceRecord($_record);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (self::ACTION_DELETE === $_action) {
                // already removed - no problem
            } else {
                throw $tenf;
            }
        }
        return true;
    }

    protected function getSourceRecord($_record): Tinebase_Record_Interface
    {
        if ($_record->isFSNode()) {
            $ctrl = Filemanager_Controller_Node::getInstance();
        } else {
            $ctrl = Felamimail_Controller_Message::getInstance();
        }
        return $ctrl->get($_record->{Felamimail_Model_AttachmentCache::FLD_SOURCE_ID}, null, false);
    }

    public function fillAttachmentCache(array $accountIds, ?int $seconds = null): void
    {
        $lockKey = __METHOD__ . Tinebase_Core::getUser()->getId();
        if (false === Tinebase_Core::acquireMultiServerLock($lockKey)) {
            return;
        }
                                            // 4 weeks
        if (null === $seconds || $seconds > 4 * 7 * 24 * 3600) {
            $seconds = 2 * 7 * 24 * 3600; // 2 weeks
        }
        $old = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->doSynchronousPreviewCreation(true);
        $lastKeepAlive = time();
        try {
            foreach (Felamimail_Controller_Account::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                        ['field' => 'id', 'operator' => 'in', 'value' => $accountIds],
                    ]
                ), null, false, true) as $accountId) {
                $msgCtrl = Felamimail_Controller_Message::getInstance();
                foreach ($msgCtrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
                    ['field' => 'received', 'operator' => 'after', 'value' => Tinebase_DateTime::now()->subSecond($seconds)],
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => $accountId],
                    ['field' => 'has_attachment', 'operator' => 'equals', 'value' => true],
                ]), new Tinebase_Model_Pagination(['sort' => 'received', 'dir' => 'DESC']), false, true) as $msgId) {
                    foreach ($msgCtrl->getAttachments($msgId) as $attachment) {
                        $this->get(Felamimail_Model_Message::class . ':' . $msgId . ':' . $attachment['partId']);
                        if (time() - $lastKeepAlive > 10) {
                            Tinebase_Core::getMultiServerLock($lockKey)->keepAlive();
                            $lastKeepAlive = time();
                        }
                    }
                }
            }
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zdse->getMessage());
        } finally {
            Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->doSynchronousPreviewCreation($old);
            try {
                Tinebase_Core::releaseMultiServerLock($lockKey);
            } catch (Tinebase_Exception_Backend $teb) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $teb->getMessage());
            }
        }
    }
}
