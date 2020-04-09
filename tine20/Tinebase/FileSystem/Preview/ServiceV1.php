<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_ServiceV1 implements Tinebase_FileSystem_Preview_ServiceInterface
{
    protected $_url;

    /**
     * @const integer timeout in seconds
     */
    const ASYNC_REQUEST_TIMEOUT = 1200;

    public function __construct()
    {
        $this->_url = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL};
    }

    /**
     * Uses the DocumentPreviewService to generate previews (images or pdf) for a file.
     *
     * {@inheritDoc}
     *
     * @param $_filePath
     * @param array $_config
     * @return array|bool
     * @throws Zend_Http_Client_Exception
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     */
    public function getPreviewsForFile($_filePath, array $_config)
    {
        if (isset($_config['synchronRequest']) && $_config['synchronRequest']) {
            $synchronRequest = true;
        } else {
            $synchronRequest = false;
        }

        $httpClient = $this->_getHttpClient($synchronRequest);
        $httpClient->setMethod(Zend_Http_Client::POST);
        $httpClient->setParameterPost('config', json_encode($_config));
        $httpClient->setFileUpload($_filePath, 'file');
        return $this->_requestPreviews($httpClient, $synchronRequest);
    }

    /**
     * Uses the DocumentPreviewService to generate previews (images or pdf files) for multiple files of same type.
     *
     * {@inheritDoc}
     *
     * @param $filePaths array of file Paths to convert
     * @param array $config
     * @return array|bool
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getPreviewsForFiles(array $filePaths, array $config)
    {
        throw new Tinebase_Exception_NotImplemented("GetPreviewsForFiles not implemented in Preview_ServiceV1");
    }

    /**
     * @param boolean $_synchronRequest
     * @return Zend_Http_Client
     */
    protected function _getHttpClient($_synchronRequest)
    {
        return Tinebase_Core::getHttpClient($this->_url, $this->_getHttpClientConfig($_synchronRequest));
    }

    protected function _getHttpClientConfig($_synchronRequest)
    {
        return [
            'timeout' => ($_synchronRequest ? 10 : self::ASYNC_REQUEST_TIMEOUT),
            'noProxy' => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                ->{Tinebase_Config::FILESYSTEM_PREVIEW_IGNORE_PROXY},
        ];
    }

    protected function _processJsonResponse(array $responseJson)
    {
        $response = array();
        foreach ($responseJson as $key => $urls) {
            $response[$key] = array();
            foreach ($urls as $url) {
                $blob = file_get_contents($url);
                if (false === $blob) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' couldn\'t read fileblob from url: ' . $url);
                    }
                    return false;
                }
                $response[$key][] = $blob;
            }
        }

        return $response;
    }

    /**
     * @param $httpClient Zend_Http_Client
     * @param $synchronRequest bool
     * @return bool
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     */
    protected function _requestPreviews($httpClient, $synchronRequest)
    {
        $tries = 0;
        $timeStarted = time();
        $responseJson = null;
        do {
            $lastRun = time();
            try {
                $response = $httpClient->request();
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' preview service call failed: ' . $e->getMessage());
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e);
                }
                if ($synchronRequest) {
                    return false;
                }
                continue;
            }

            if ($response != null) {
                switch ((int)$response->getStatus()) {
                    case 200:
                        $responseJson = json_decode($response->getBody(), true);
                        if (is_array($responseJson)) {
                            return $this->_processJsonResponse($responseJson);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Got empty/non-json response');
                            }
                            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Last request: ' . $httpClient->getLastRequest());
                            }
                        }
                        return false;
                    case 400:
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                            Tinebase_Core::getLogger()->WARN(
                                __METHOD__ . '::' . __LINE__ . ' STATUS CODE: ' . $response->getStatus() . ' MESSAGE: ' . $response->getBody()
                            );
                        }
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Last request: ' . $httpClient->getLastRequest());
                        }
                        throw new Tinebase_FileSystem_Preview_BadRequestException(
                            "Preview creation failed. Status Code: " . $response->getStatus(),
                            $response->getStatus()
                        );
                    case 413:
                    case 415:
                    case 422:
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(
                                __METHOD__ . '::' . __LINE__ . ' STATUS CODE: ' . $response->getStatus() . ' MESSAGE: ' . $response->getBody()
                            );
                        }
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Last request: ' . $httpClient->getLastRequest());
                        }
                        throw new Tinebase_FileSystem_Preview_BadRequestException(
                            "Preview creation failed. Status Code: " . $response->getStatus(),
                            $response->getStatus()
                        );
                    default:
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__ . ' STATUS CODE: ' . $response->getStatus() . ' MESSAGE: ' . $response->getBody()
                            );
                        }
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Last request: ' . $httpClient->getLastRequest());
                        }
                        if ($synchronRequest) {
                            return false;
                        }
                        break;
                }
            }

            $run = time() - $lastRun;
            if ($run < 5) {
                sleep(5 - $run);
            }
        } while (++$tries < 4 && time() - $timeStarted < 180);

        return false;
    }

    /**
     * Uses the DocumentPreviewService to generate a pdf for a documentfile.
     *
     * @param $filePath
     * @param $synchronRequest bool should the request be prioritized
     * @param array $intermediateFormats
     * @return string file blob
     * @throws Tinebase_Exception_NotImplemented
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     * @throws Zend_Http_Client_Exception
     */
    public function getPdfForFile($filePath, $synchronRequest = false, $intermediateFormats = [])
    {
        if ([] !== $intermediateFormats) {
            throw new Tinebase_Exception_NotImplemented("getPdfForFile with intermediate formats not implemented in Preview_ServiceV1");
        }
        return $this->_getSingleFile($filePath, ['fileType' => 'pdf',], $synchronRequest);
    }

    /**
     * @param $filePath
     * @param $config
     * @param $synchronRequest
     * @return mixed
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     * @throws Zend_Http_Client_Exception
     */
    protected function _getSingleFile($filePath, $config, $synchronRequest)
    {
        if (false === ($result = $this->getPreviewsForFile($filePath, ['synchronRequest' => $synchronRequest, $config]))) {
            Tinebase_Core::getLogger()->err(__METHOD__ . ' ' . __LINE__ . ' preview service did not succeed');
            throw new Tinebase_Exception_UnexpectedValue('preview service did not succeed: service occupied');
        }
        return $result[0][0];
    }

    /**
     * Merges multiple pdf files into a single one.
     *
     * @param $filePaths array of file paths
     * @param bool $synchronousRequest
     * @return string path to file
     */
    public function mergePdfFiles($filePaths, $synchronousRequest = false)
    {
        throw new Tinebase_Exception_NotImplemented("MergePdfFiles not implemented in Preview_ServiceV1");
    }
}
