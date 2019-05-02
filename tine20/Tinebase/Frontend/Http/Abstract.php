<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http interface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Http_Abstract extends Tinebase_Frontend_Abstract
{
    /**
     * generic export function
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_options format/definition id
     * @param Tinebase_Controller_Record_Abstract $_controller
     * @return void
     * 
     * @todo support single ids as filter?
     * @todo use stream here instead of temp file?
     */
    protected function _export(Tinebase_Model_Filter_FilterGroup $_filter, $_options, Tinebase_Controller_Record_Abstract $_controller = NULL)
    {
        // extend execution time to 30 minutes
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(1800);
        
        // get export object
        $export = Tinebase_Export::factory($_filter, $_options, $_controller);
        $format = $export->getFormat();
        if ('pdf' === $format && ! Tinebase_Export::doPdfLegacyHandling()) {
            $switchFormat = 'newPDF';
        } else {
            $switchFormat = $format;
        }

        if (strpos($format, 'new') === 0) {
            $format = strtolower(substr($format, 3));
        }

        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exporting ' . $_filter->getModelName() . ' in format ' . $format);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_options, TRUE));

        try {
            switch ($switchFormat) {
                case 'pdf':
                    $ids = $_controller->search($_filter, NULL, FALSE, TRUE, 'export');

                    // loop records
                    foreach ($ids as $id) {
                        if (! empty($id)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating pdf for ' . $_filter->getModelName() . '  id ' . $id);
                            $record = $_controller->get($id);
                            $export->generate($record);
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $_filter->getModelName() . ' id empty!');
                        }
                    }

                    // render pdf
                    try {
                        $pdfOutput = $export->render();
                    } catch (Zend_Pdf_Exception $e) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
                        exit;
                    }

                    break;

                case 'ods':
                    $result = $export->generate();
                    break;
                case 'newOds':
                case 'newPDF':
                case 'newCsv':
                case 'csv':
                case 'xls':
                case 'xlsx':
                case 'doc':
                case 'docx':
                    $result = $export->generate($_filter);
                break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue('Format ' . $format . ' not supported.');
            }
        } catch (Tinebase_Exception_UnexpectedValue $e) {
            if ($e->getMessage() === 'Format ' . $format . ' not supported.') {
                throw $e;
            }
            $result = null;
            $export = new Tinebase_Export_ErrorReport($e);
            $format = 'txt';
            $switchFormat = 'error';
        } catch (Exception $e) {
            if (strpos(get_class($e), 'Zend_Db') === 0) {
                throw $e;
            }
            $result = null;
            $export = new Tinebase_Export_ErrorReport($e);
            $format = 'txt';
            $switchFormat = 'error';
        }

        // write headers
        $contentType = $export->getDownloadContentType();
        $filename = $export->getDownloadFilename($_filter->getApplicationName(), $format);

        if (! headers_sent()) {
            header("Pragma: public");
            header("Cache-Control: max-age=0");
            header("Content-Disposition: " . (($format == 'pdf') ? 'inline' : 'attachment') . '; filename=' . $filename);
            header("Content-Description: $format File");
            header("Content-type: $contentType");
        }
        
        // output export file
        switch ($switchFormat) {
            case 'pdf':
                echo $pdfOutput;
                break;
            case 'newOds':
            case 'newCsv':
            case 'newPDF':
            case 'xls':
            case 'xlsx':
            case 'doc':
            case 'docx':
            case 'error':
                // redirect output to client browser
                if (null === $result) {
                    $export->write();
                } else {
                    $export->write($result);
                }
                break;
            default:
                readfile($result);
                unlink($result);
        }
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_type
     * @param int $_num
     * @throws Tinebase_Exception_NotFound
     */
    protected function _downloadPreview(Tinebase_Model_Tree_Node $_node, $_type, $_num = 0)
    {
        $fileSystem = Tinebase_FileSystem::getInstance();

        $previewNode = Tinebase_FileSystem_Previews::getInstance()->getPreviewForNode($_node, $_type, $_num);

        $this->_prepareHeader($previewNode->name, $previewNode->contenttype, 'inline', $previewNode->size);

        $handle = fopen($fileSystem->getRealPathForHash($previewNode->hash), 'r');

        if (false === $handle) {
            throw new Tinebase_Exception_NotFound('could not open preview by real path for hash');
        }

        fpassthru($handle);

        fclose($handle);
    }


    /**
     * download (fpassthru) tempfile
     *
     * @param Tinebase_Model_Tree_Node $node
     * @param string $filesystemPath
     * @param int|null $revision
     * @param boolean $ignoreAcl
     * @throws Tinebase_Exception_NotFound
     */
    protected function _downloadTempFile(Tinebase_Model_TempFile $tempFile, $filesystemPath)
    {
        Tinebase_Core::setExecutionLifeTime(0);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Download tempfile' . print_r($tempFile->toArray(), TRUE));

        $this->_prepareHeader($tempFile->name, $tempFile->contenttype, /* $disposition */ 'attachment', $tempFile->size);

        $handle = fopen($filesystemPath, 'r');

        fpassthru($handle);
        fclose($handle);
    }

    /**
     * download (fpassthru) file node
     * 
     * @param Tinebase_Model_Tree_Node $node
     * @param string $filesystemPath
     * @param int|null $revision
     * @param boolean $ignoreAcl
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _downloadFileNode(Tinebase_Model_Tree_Node $node, $filesystemPath, $revision = null, $ignoreAcl = false)
    {
        if (! $ignoreAcl && ! Tinebase_Core::getUser()->hasGrant($node, Tinebase_Model_Grants::GRANT_DOWNLOAD)) {
            throw new Tinebase_Exception_AccessDenied('download not allowed');
        }

        Tinebase_Core::setExecutionLifeTime(0);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Download file node ' . print_r($node->toArray(), TRUE));

        $this->_prepareHeader($node->name, $node->contenttype, /* $disposition */ 'attachment', $node->size);

        if (null !== $revision) {
            $streamContext = stream_context_create(array(
                'Tinebase_FileSystem_StreamWrapper' => array(
                    'revision' => $revision
                )
            ));
            $handle = @fopen($filesystemPath, 'r', false, $streamContext);
        } else {
            $handle = @fopen($filesystemPath, 'r');
        }

        if ($handle) {
            fpassthru($handle);
            fclose($handle);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not open file: ' . $filesystemPath);
        }
    }

    /**
     * prepares the header for attachment download
     *
     * @param string $filename
     * @param string $contentType
     * @param string $disposition
     * @param string $length
     *
     * TODO make length param work
     * @see 0010522: Anonymous download link - no or wrong filesize in header
     */
    protected function _prepareHeader($filename, $contentType, $disposition = 'attachment', $length = null)
    {
        if (headers_sent()) {
            return;
        }

        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");

        // overwrite Pragma header from session
        header("Pragma: cache");

        if ($disposition) {
            header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        }
        header("Content-Type: " . $contentType);

//        if ($length) {
//            header("Content-Length: " . $length);
//        }
    }

    /**
     * magic method for http api
     *
     * @param string $method
     * @param array  $args
     */
    public function __call($method, array $args)
    {
        // provides api for default application methods
        if (preg_match('/^(export)([a-z0-9]+)/i', $method, $matches)) {
            $apiMethod = $matches[1];
            $model = in_array($apiMethod, array('export')) ? substr($matches[2],0,-1) : $matches[2];
            $modelController = Tinebase_Core::getApplicationInstance($this->_applicationName, $model);
            switch ($apiMethod) {
                case 'export':
                    $decodedParams = $this->_getDecodedFilterAndOptions($args[0], $args[1]);

                    $modelName = $this->_applicationName . '_Model_' . $model;
                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($modelName);
                    $filter->setFromArrayInUsersTimezone($decodedParams['filter']);

                    return $this->_export($filter, $decodedParams['options'], $modelController);
                    break;
            }
        }
    }

    /**
     * @param string $filterString
     * @param string $optionsString
     * @return array
     */
    protected function _getDecodedFilterAndOptions($filterString, $optionsString)
    {
        $decodedFilter = Tinebase_Helper::is_json($filterString) ? $this->_prepareParameter($filterString) : $filterString;
        $decodedOptions = $this->_prepareParameter($optionsString);

        if (empty($decodedFilter) && isset($decodedOptions['recordData']['id'])) {
            // get contact id from $decodedOptions
            $decodedFilter = $decodedOptions['recordData']['id'];
        }

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        return [
            'filter' => $decodedFilter,
            'options' => $decodedOptions,
        ];
    }

    /**
     * receives file uploads and stores it in the file_uploads db
     *
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_NotFound
     */
    protected function _uploadTempFile()
    {
        try {
            // close session to allow other requests
            Tinebase_Session::writeClose(true);

            $tempFile = Tinebase_TempFile::getInstance()->uploadTempFile();

            die(Zend_Json::encode(array(
                'status'   => 'success',
                'tempFile' => $tempFile->toArray(),
            )));
        } catch (Tinebase_Exception $exception) {
            Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " File upload could not be done, due to the following exception: \n" . $exception);

            if (! headers_sent()) {
                header("HTTP/1.0 500 Internal Server Error");
            }
            die(Zend_Json::encode(array(
                'status'   => 'failed',
            )));
        }
    }
}
