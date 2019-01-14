<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function __construct()
    {
        $this->_url = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL};
    }

    /**
     * @param $_filePath
     * @param array $_config
     * @return array|bool
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

        $tries = 0;
        $timeStarted = time();
        $responseJson = null;
        do {
            $lastRun = time();
            try {
                $response = $httpClient->request();
                if ((int)$response->getStatus() === 200) {
                    $responseJson = json_decode($response->getBody(), true);
                    break;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                            . __LINE__ . ' STATUS CODE: ' . $response->getStatus() . ' MESSAGE: ' . $response->getBody());
                    }
                    if ($synchronRequest) {
                        return false;
                    }
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                        . __LINE__ . ' preview service call failed: ' . $e->getMessage());
                }
                if ($synchronRequest) {
                    return false;
                }
            }
            $run = time() - $lastRun;
            if ($run < 5) {
                sleep(5 - $run);
            }
        } while(++$tries < 4 && time() - $timeStarted < 180);

        if (is_array($responseJson)) {
            return $this->_processJsonResponse($responseJson);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                . '::' . __LINE__ . ' Got empty/non-json response');
        }

        return false;
    }

    protected function _processJsonResponse(array $responseJson)
    {
        $response = array();
        foreach($responseJson as $key => $urls) {
            $response[$key] = array();
            foreach($urls as $url) {
                $blob = file_get_contents($url);
                if (false === $blob) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                        . '::' . __LINE__ . ' couldn\'t read fileblob from url: ' . $url);
                    return false;
                }
                $response[$key][] = $blob;
            }
        }

        return $response;
    }

    /**
     * @param boolean $_synchronRequest
     * @return Zend_Http_Client
     */
    protected function _getHttpClient($_synchronRequest)
    {
        return Tinebase_Core::getHttpClient($this->_url, array('timeout' => ($_synchronRequest ? 10 : 300)));
    }
}