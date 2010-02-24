<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Cli.php 5147 2008-10-28 17:03:33Z p.schuele@metaways.de $
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
     * Returns all JS files which must be included for Setup
     *
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Setup/js/init.js',
            'Setup/js/Setup.js',
            'Setup/js/MainScreen.js',
            'Setup/js/TermsPanel.js',
            'Setup/js/ApplicationGridPanel.js',
            'Setup/js/EnvCheckGridPanel.js',
            'Setup/js/ConfigManagerPanel.js',
            'Setup/js/AuthenticationPanel.js',
            'Setup/js/EmailPanel.js',
        );
    }
    
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
        $view->setScriptPath('Setup/views');
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('jsclient.php');
    }
    
    
    /**
     * returns an array with all css and js files which needs to be included
     * 
     * @return array 
     */
    public static function getAllIncludeFiles() {
        // we start with all Tinebase include files ...
        $tinebase = new Tinebase_Frontend_Http();
        $jsFiles  = $tinebase->getJsFilesToInclude();
        $cssFiles = $tinebase->getCssFilesToInclude();
        
        // ... and add only the setup files
        $setup = new Setup_Frontend_Http();
        return array(
            'js'  => array_merge($jsFiles, $setup->getJsFilesToInclude()),
            'css' => array_merge($cssFiles, $setup->getCssFilesToInclude())
        );
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
