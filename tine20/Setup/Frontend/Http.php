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
     * authentication
     *
     * @param string $_username
     * @param string $_password
     */
    public function authenticate($_username, $_password)
    {
        return false;
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @return boolean success
     */
    public function handle()
    {
        if (isset($_REQUEST['method']) && $_REQUEST['method'] == 'Tinebase.getJsTranslations') {
            $locale = Setup_Core::get('locale');
            $translations = Tinebase_Translation::getJsTranslations($locale);
            header('Content-Type: application/javascript');
            die($translations);
        }
        
        return $this->mainScreen();
    }
}
