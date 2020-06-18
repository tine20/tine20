<?php
/**
 * Tine 2.0
 * @package     Tinebase_Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Tinebase_Export
 *
 */
abstract class Tinebase_Export_Report_Abstract extends Tinebase_Export_Abstract
{
    /**
     * @var Tinebase_Model_Tree_FileLocation
     */
    protected $_fileLocation = null;

    /**
     * @var array
     */
    protected $_downloadFilePaths = [];

    public function generate()
    {
        $this->_checkOptions();

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Generating REPORT export (' . $this->_config->name . ')');
        }

        // TODO define export result - is it an object? recordset of filelocations?
        $exportResult = [];
        foreach ($this->_config->sources->toArray() as $containerData) {
            if (is_string($containerData)) {
                $containerId = $containerData;
            } else if (isset($containerData['id'])) {
                $containerId = $containerData['id'];
            } else {
                // no container / id
                continue;
            }
            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
            $exportResult[] = [
                'filename' => $this->_exportContainer($container),
                'container' => $container,
            ];
        }

        $this->_saveExportFilesToFileLocation($exportResult);

        return $exportResult;
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     *
     * @todo add default target?
     */
    protected function _checkOptions()
    {
        if (! isset($this->_config->sources) || ! isset($this->_config->target)) {
            throw new Tinebase_Exception_InvalidArgument('sources and/or filelocation options missing / invalid');
        }

        $this->_fileLocation = new Tinebase_Model_Tree_FileLocation($this->_config->target->toArray());
    }

    /**
     * @param Tinebase_Model_Container $container
     * @return string export filename
     */
    protected function _exportContainer(Tinebase_Model_Container $container)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_config->model, [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
        ]);
        $export = new $this->_exportClass($filter, null, [
            'filename' => Tinebase_TempFile::getTempPath(),
        ]);
        return $export->generate();
    }

    /**
     * @param array $exportResult
     * @throws Tinebase_Exception_NotImplemented
     *
     * @todo support all possible file locations (attachment + local still missing)
     * @todo build zip file for multiple files
     */
    protected function _saveExportFilesToFileLocation($exportResult)
    {
        foreach ($exportResult as $generatedExport) {
            switch ($this->_fileLocation->type) {
                case Tinebase_Model_Tree_FileLocation::TYPE_FM_NODE:
                    $this->_saveToFilemanager($generatedExport);
                    break;
                case Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD:
                    $this->_downloadFilePaths[] = $generatedExport['filename'];
                    break;
                default:
                    throw new Tinebase_Exception_NotImplemented(
                        'FileLocation type ' . $this->_fileLocation->type . ' not implemented yet');
            }
        }
    }

    protected function _saveToFilemanager($generatedExport)
    {
        $filename = $this->_getExportFilename($generatedExport['container']);
        $tempFile = Tinebase_TempFile::getInstance()->createTempFile($generatedExport['filename']);

        $nodePath = Tinebase_Model_Tree_Node_Path::createFromRealPath($this->_fileLocation->fm_path,
            Tinebase_Application::getInstance()->getApplicationByName('Filemanager'));
        Tinebase_FileSystem::getInstance()->copyTempfile($tempFile,$nodePath->statpath . '/' . $filename);
    }

    /**
     * @param Tinebase_Model_Container $container
     * @return string
     */
    protected function _getExportFilename($container)
    {
        return str_replace([' ', DIRECTORY_SEPARATOR], '', $container->name . '.' . $this->_format);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function write()
    {
        if ($this->_fileLocation->type === Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD) {
            foreach ($this->_downloadFilePaths as $path) {
                $handle = fopen($path, 'r');
                if (false === $handle) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not open download file');
                    continue;
                }
                fpassthru($handle);
                fclose($handle);
            }
        }
    }

    /**
     * @return bool
     */
    public function isDownload()
    {
        return parent::isDownload() && $this->_fileLocation->type === Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD;
    }

    /**
     * add information to file location / create filelocation if isDownload
     *
     * @param null|array|string $filename
     * @return Tinebase_Model_Tree_FileLocation|null
     */
    public function getTargetFileLocation($filename = null)
    {
        if ($this->_config->returnFileLocation && $this->_fileLocation->type === Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD) {
            if (count($filename) > 1) {
                $filename = $this->_zipFiles($filename);
                $this->_format = 'zip';
            } else {
                $firstFile = array_pop($filename);
                $filename = $firstFile['filename'];
            }

            return parent::getTargetFileLocation($filename);
        } else {
            return $this->_fileLocation;
        }
    }

    /**
     * @param $exportFiles
     * @return string
     * @throws Exception
     *
     * TODO add a zip helper
     */
    protected function _zipFiles($exportFiles)
    {
        $zip = new ZipArchive();
        $zipfilename = Tinebase_TempFile::getTempPath();
        $opened = $zip->open($zipfilename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

        if( $opened !== true ) {
            throw new Exception('could not open zip file');
        }
        foreach ($exportFiles as $file) {
            $zip->addFile($file['filename'], $this->_getExportFilename($file['container']));
        }
        $zip->close();

        return $zipfilename;
    }
}
