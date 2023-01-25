<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

trait Tinebase_Export_FileLocationTrait
{
    protected $_config;
    protected $_fileLocation;

    public abstract function save($target = null);
    public abstract function getDownloadFilename();

    public function isDownload(): bool
    {
        return (null === $this->_fileLocation ||
                $this->_fileLocation->type === Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD)
            && !$this->_config->returnFileLocation;
    }

    public function writeToFileLocation(): void
    {
        if (!$this->_fileLocation) {
            return;
        }
        switch ($this->_fileLocation->{Tinebase_Model_Tree_FileLocation::FLD_TYPE}) {
            case Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE:
                $fmCtrl = Filemanager_Controller_Node::getInstance();
                $trgtPath = Tinebase_Model_Tree_Node_Path::createFromPath($fmCtrl->addBasePath($this->_fileLocation
                    ->{Tinebase_Model_Tree_FileLocation::FLD_FM_PATH}));
                $fs = Tinebase_FileSystem::getInstance();
                $fs->checkPathACL($trgtPath, 'add', false);

                $fileName = $this->getDownloadFilename();
                $pInfo = pathinfo($fileName);
                if (strlen((string)$this->_fileLocation->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME}) > 0) {
                    $pInfoFL = pathinfo($this->_fileLocation->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME});
                    $fileName = $pInfoFL['filename'] . '.' . $pInfo['extension'];
                    $pInfo['filename'] = $pInfoFL['filename'];
                }
                $targetPath = $trgtPath->statpath . '/' . $fileName;
                $i = 0;

                while (Tinebase_FileSystem::getInstance()->fileExists($targetPath)) {
                    $targetPath = $trgtPath->statpath . '/' . $pInfo['filename'] . '(' . (++$i) . ').' . $pInfo['extension'];
                }

                $this->save('tine20://' . $targetPath);
                break;

            case Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT:
                $fileName = $this->getDownloadFilename();
                $pInfo = pathinfo($fileName);
                if (strlen((string)$this->_fileLocation->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME}) > 0) {
                    $pInfo = pathinfo($fileName);
                    $pInfoFL = pathinfo($this->_fileLocation->{Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME});
                    $fileName = $pInfoFL['filename'] . '.' . $pInfo['extension'];
                    $pInfo['filename'] = $pInfoFL['filename'];
                }
                $targetNode = new Tinebase_Model_Tree_Node([
                    'name' => $fileName,
                    'tempFile' => Tinebase_TempFile::getTempPath(),
                ], true);

                try {
                    $this->save($targetNode->tempFile);

                    list($record, $ctrl) = $this->_fileLocation->getAttachmentRecordAndCtrl();
                    Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
                    $i = 0;
                    while ($record->attachments->find('name', $fileName)) {
                        $fileName = $pInfo['filename'] . '(' . (++$i) . ').' . $pInfo['extension'];
                        $targetNode->name = $fileName;
                    }
                    $record->attachments->addRecord($targetNode);
                    $ctrl->update($record);
                } finally {
                    @unlink($targetNode->tempFile);
                }
                break;

            default:
                throw new Tinebase_Exception_NotImplemented(
                    'FileLocation type ' . $this->_fileLocation->type . ' not implemented yet');
        }
    }

    public function getTargetFileLocation($filename = null): Tinebase_Model_Tree_FileLocation
    {
        if ($this->_fileLocation && $this->_fileLocation->type !== Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD) {
            return $this->_fileLocation;
        }

        if ($filename === null) {
            if (method_exists($this, 'write')) {
                ob_start();
                $fh = null;
                try {
                    $this->write($fh = fopen('php://temp', 'w+'));
                    $output = ob_get_clean();
                    if (false === $output || 0 === strlen($output)) {
                        rewind($fh);
                        $output = stream_get_contents($fh);
                    }
                } finally {
                    if ($fh) {
                        fclose($fh);
                    }
                }
                $filename = Tinebase_TempFile::getTempPath();
                file_put_contents($filename, $output);
            } else {
                throw new Tinebase_Exception_NotImplemented('Not implemented for this export');
            }
        }

        $tempFile = Tinebase_TempFile::getInstance()->createTempFile($filename, $this->getDownloadFilename());
        return new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD,
            Tinebase_Model_Tree_FileLocation::FLD_TEMPFILE_ID => $tempFile->getId(),
        ]);
    }
}