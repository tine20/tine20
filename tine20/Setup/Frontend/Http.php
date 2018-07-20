<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * http server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Setup_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Setup';
    
    /**
     * get json-api service map
     * 
     * @return string
     */
    public static function getServiceMap()
    {
        $server = new Zend_Json_Server();
        
        $server->setClass('Setup_Frontend_Json', 'Setup');
        $server->setClass('Tinebase_Frontend_Json', 'Tinebase');
        
        $server->setTarget('setup.php')
               ->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
            
        $smd = $server->getServiceMap();
        
        $smdArray = $smd->toArray();
        unset($smdArray['methods']);
        
        return $smdArray;
    }

    /**
     * display Tine 2.0 main screen
     */
    public function mainScreen()
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = ['Setup/js/fatClient.js'];
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

        $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
        if (! empty($customJSFiles)) {
            $jsFiles[] = "index.php?method=Tinebase.getCustomJsFiles";
        }

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/FATClient.html.twig');
    }
    
    /**
     * download config as config file
     * 
     * @param array $data
     */
    public function downloadConfig($data)
    {
        if (! Setup_Core::isRegistered(Setup_Core::USER)) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        if (! Setup_Core::configFileExists()) {
            $data = Zend_Json::decode($data, Zend_Json::TYPE_ARRAY);
            
            $tmpFile = tempnam(Tinebase_Core::getTempDir(), 'tine20_');
            Setup_Controller::getInstance()->writeConfigToFile($data, TRUE, $tmpFile);
            
            $configData = file_get_contents($tmpFile);
            unlink($tmpFile);
            
            header("Pragma: public");
            header("Cache-Control: max-age=0");
            header("Content-Disposition: attachment; filename=config.inc.php");
            header("Content-Description: PHP File");
            header("Content-type: text/plain");
            die($configData);
        }
    }

    /**
     * receives file uploads and stores it in the file_uploads db
     *
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_NotFound
     */
    public function uploadTempFile()
    {
        if (Setup_Core::isRegistered(Setup_Core::USER)) {
            $this->_uploadTempFile();
        } else {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
    }
}
