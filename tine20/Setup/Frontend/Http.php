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
     * renders the tine main screen 
     */
    public function mainScreen()
    {
        //$this->checkAuth();
        
        $view = new Zend_View();
        $baseDir = dirname(dirname(dirname(__FILE__)));
        $view->setScriptPath("$baseDir/Setup/views");
        
        $appNames = array('Tinebase', 'Setup');
        
        require_once 'jsb2tk/jsb2tk.php';
        $view->jsb2tk = new jsb2tk(array(
            'deploymode'    => jsb2tk::DEPLOYMODE_STATIC,
            'includemode'   => jsb2tk::INCLUDEMODE_INDIVIDUAL,
            'appendctime'   => TRUE,
            'htmlindention' => "    ",
        ));
        
        foreach($appNames as $appName) {
            $view->jsb2tk->register("$baseDir/$appName/$appName.jsb2", $appName);
        }
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('jsclient.php');
    }
    
    /**
     * download config as config file
     * 
     * @param array $data
     */
    public function downloadConfig($data)
    {
        if (! Setup_Core::configFileExists() || Setup_Core::isRegistered(Setup_Core::USER)) {
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
}
