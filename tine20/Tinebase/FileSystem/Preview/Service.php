<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_Service implements Tinebase_FileSystem_Preview_ServiceInterface
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
        $httpClient = Tinebase_Core::getHttpClient($this->_url);
        $httpClient->setMethod(Zend_Http_Client::POST);
        $httpClient->setParameterPost('config', json_encode($_config));
        $httpClient->setFileUpload($_filePath, 'file');

        $tries = 0;
        $timeStarted = time();
        $responseJson = null;
        do {
            $lastRun = time();
            $response = $httpClient->request();
            if ((int)$response->getStatus() === 200) {
                $responseJson = json_decode($response->getBody(), true);
                break;
            }
            $run = time() - $lastRun;
            if ($run < 5) {
                sleep(5 - $run);
            }
        } while(++$tries < 4 && time() - $timeStarted < 180);

        if (is_array($responseJson)) {
            $response = array();
            foreach($responseJson as $key => $urls) {
                $response[$key] = array();
                foreach($urls as $url) {
                    $blob = file_get_contents($url);
                    if (false === $blob) {
                        return false;
                    }
                    $response[$key][] = $blob;
                }
            }

            return $response;
        }

        return false;
    }
}