<?php declare(strict_types=1);

/**
 * AttachmentCache controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function get($_id, $_containerId = null, $_getRelatedData = true, $_getDeleted = false, $_aclProtect = true)
    {
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
            return $record;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . ' creating cache for ' . $_id);
            return $this->create(new Felamimail_Model_AttachmentCache(['id' => $_id]));
        }
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Felamimail_Model_AttachmentCache $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $ctrl = Felamimail_Controller_Message::getInstance();

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
            $msgPart = $ctrl->getMessagePart($_record->{Felamimail_Model_AttachmentCache::FLD_SOURCE_ID},
                $_record->{Felamimail_Model_AttachmentCache::FLD_PART_ID});
            $stream = $msgPart->getDecodedStream();
            $fileName = $msgPart->filename;
        }
        rewind($stream);

        $_record->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [
            new Tinebase_Model_Tree_Node([
                'name' => $fileName,
                'tempFile' => true,
                'stream' => $stream,
            ], true)
        ]);
    }

    /**
     * @param Felamimail_Model_AttachmentCache $_record
     * @param string $_action
     * @param bool $_throw
     * @param string $_errorMessage
     * @param null $_oldRecord
     * @return bool
     */
    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if ('update' === $_action) {
            throw new Tinebase_Exception_AccessDenied(Felamimail_Model_AttachmentCache::class . ' may not be updated');
        }
        $this->getSourceRecord($_record);
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
}
