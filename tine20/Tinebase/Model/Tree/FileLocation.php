<?php
/**
 * class to hold FileLocation data
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c)2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold FileLocation data, it can either be a Filemanager path or it can be a record attachment
 * it might be something else in the future, amend if required
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * TODO refactor: dynamic model with config depending on selected type, because download / local don't have node (etc.)
 */
class Tinebase_Model_Tree_FileLocation extends Tinebase_Record_NewAbstract
{
    const TYPE_FM_NODE = 'fm_node';
    const TYPE_ATTACHMENT = 'attachement';
    // user gets a download
    const TYPE_DOWNLOAD = 'download';
    // local filesystem, i.e. for CLI use
    const TYPE_LOCAL = 'local';

    const FLD_TYPE = 'type';
    const FLD_MODEL = 'model';
    const FLD_RECORD_ID = 'record_id';
    const FLD_FILE_NAME = 'file_name';
    const FLD_FM_PATH = 'fm_path';
    const FLD_NODE_ID = 'node_id';
    const FLD_REVISION = 'revision';
    const FLD_TEMPFILE_ID = 'tempfile_id';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::FIELDS    => [
            self::FLD_TYPE          => [
                self::TYPE => self::TYPE_STRING,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    ['InArray', [self::TYPE_ATTACHMENT, self::TYPE_FM_NODE, self::TYPE_DOWNLOAD, self::TYPE_LOCAL]],
                ],
            ],
            // filemanager fields
            self::FLD_FM_PATH       => [
                self::TYPE              => self::TYPE_STRING,
            ],
            // attachment fields
            self::FLD_MODEL         => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_RECORD_ID     => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_FILE_NAME     => [
                self::TYPE              => self::TYPE_STRING,
            ],
            // VFS fields
            self::FLD_NODE_ID       => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_REVISION      => [
                self::TYPE              => self::TYPE_INTEGER,
            ],
            // download fields
            self::FLD_TEMPFILE_ID      => [
                self::TYPE              => self::TYPE_STRING,
            ],
        ],
    ];

    public function copyNodeTo(Tinebase_Model_Tree_Node $srcNode)
    {
        switch ($this->{self::FLD_TYPE}) {
            case self::TYPE_FM_NODE:
                $fmCtrl = Filemanager_Controller_Node::getInstance();
                $trgtPath = Tinebase_Model_Tree_Node_Path::createFromStatPath($fmCtrl->addBasePath($this
                    ->{self::FLD_FM_PATH}));
                $fs = Tinebase_FileSystem::getInstance();
                $fs->checkPathACL($trgtPath->getParent(), 'update', FALSE);
                if ($fs->fileExists($trgtPath)) {
                    $trgtNode = $fs->stat($trgtPath);
                } else {
                    $trgtNode = $fs->createFileTreeNode($fs->stat($trgtPath->getParent()), $this->{FLD_FILE_NAME});
                }
                $trgtNode->hash = $srcNode->hash;
                $fs->update($trgtNode);
                break;
            case self::TYPE_ATTACHMENT:
                list($record, $ctrl) = $this->_getAttachmentRecordAndCtrl();
                Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
                $record->attachments->addRecord($srcNode);
                $ctrl->update($record);
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('invalid type: ' . $this->{FLD_TYPE});
        }
    }

    /**
     * @return Tinebase_Model_Tree_Node
     */
    public function getNode()
    {
        switch ($this->{self::FLD_TYPE}) {
            case self::TYPE_FM_NODE:
                $node = $this->_getFMNode();
                break;
            case self::TYPE_ATTACHMENT:
                $node = $this->_getAttachementNode();
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('invalid type: ' . $this->{self::FLD_TYPE});
        }

        if (!empty($this->{self::FLD_NODE_ID}) && $this->{self::FLD_NODE_ID} !== $node->getId()) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_FM_PATH . ' and ' . self::FLD_NODE_ID . ' mismatch');
        }
        return $node;
    }

    protected function _getAttachmentRecordAndCtrl()
    {
        list($app) = explode('_', $this->{self::FLD_MODEL});
        $recordCtrl = Tinebase_Core::getApplicationInstance($app, $this->{self::FLD_MODEL});
        $record = $recordCtrl->get($this->{self::FLD_RECORD_ID}, null, false);

        return [$record, $recordCtrl];
    }

    protected function _getAttachementNode()
    {
        list($record) = $this->_getAttachmentRecordAndCtrl();
        $fs = Tinebase_FileSystem::getInstance();
        $pNode = $fs->stat(Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($record));
        if (!empty($this->{self::FLD_NODE_ID})) {
            $node = $fs->get($this->{self::FLD_NODE_ID}, false, $this->{self::FLD_REVISION} ?: null);
            if ($node->parent_id != $pNode->getId()) {
                throw new Tinebase_Exception_UnexpectedValue(self::FLD_NODE_ID . ' contains invalid node id');
            }
            if (strlen($this->{self::FLD_FILE_NAME}) > 0 && $this->{self::FLD_FILE_NAME} !== $node->name) {
                throw new Tinebase_Exception_UnexpectedValue(self::FLD_FILE_NAME . ' mismatches nodes name');
            }
            return $node;
        }

        $node = $fs->getTreeNode($pNode, $this->{self::FLD_FILE_NAME});
        if (!empty($this->{self::FLD_REVISION}) && (int)$node->revision !== (int)$this->{self::FLD_REVISION}) {
            $node = $fs->get($node->getId(), false, $this->{self::FLD_REVISION});
        }
        return $node;
    }

    protected function _getFMNode()
    {
        $fmCtrl = Filemanager_Controller_Node::getInstance();
        return $fmCtrl->getFileNode(Tinebase_Model_Tree_Node_Path::createFromPath($fmCtrl->addBasePath($this
            ->{self::FLD_FM_PATH})), $this->{self::FLD_REVISION} ?: null);
    }
}
