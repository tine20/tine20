<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo remove cli session/cache path (add http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/ to helpers?)
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Cli implements Tinebase_Server_Interface
{
    /**
     * init tine framework
     *
     */
    protected function _initFramework()
    {
        $this->_setupCliConfig();
        Tinebase_Core::setupTempDir();
        Tinebase_Core::setupServerTimezone();
        Tinebase_Core::setupLogger();
        Tinebase_Core::setupStreamWrapper();
        Tinebase_Core::setupSession();
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('en_US'));
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, 'UTC');
        Tinebase_Core::setupDatabaseConnection();
        Tinebase_Core::setupCache();
        Tinebase_Core::setupUserTimezone();
        Tinebase_Core::setupUserLocale();
    }
    
    /**
     * initializes the config
     * - overwrite session_save_path
     */
    public function _setupCliConfig()
    {
        $configData = include('config.inc.php');
        if($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $configData['sessiondir'] = Tinebase_Core::getTempDir();
        
        $config = new Zend_Config($configData);
        Tinebase_Core::set(Tinebase_Core::CONFIG, $config);  
    }
    
    /**
     * handler for command line scripts
     * 
     * @return boolean
     */
    public function handle()
    {
        $this->_initFramework();
        
        $opts = Tinebase_Core::get('opts');

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Is cli request. method: ' . (isset($opts->method) ? $opts->method : 'EMPTY'));

        $tinebaseServer = new Tinebase_Frontend_Cli();
        if (! in_array($opts->method, array('Tinebase.triggerAsyncEvents', 'Tinebase.processQueue'))) {
            $tinebaseServer->authenticate($opts->username, $opts->password);
        }
        $result = $tinebaseServer->handle($opts);

        //@todo remove cli session path
        
        return $result;
    }
    
    /**
     * prompts user for input
     * 
     * @param  string $_promptText   prompt text to dipslay
     * @param  bool   $_isPassword  is prompt a password?
     * @return string
     */
    public static function promptInput($_promptText, $_isPassword = FALSE) {
        fwrite(STDOUT, PHP_EOL . "$_promptText> ");
        
        if ($_isPassword) {
            if (preg_match('/^win/i', PHP_OS)) {
                $pwObj = new Com('ScriptPW.Password');
                $input = $pwObj->getPassword();
            } else {
                system('stty -echo');
                $input = fgets(STDIN);
                system('stty echo');
            }
            fwrite(STDOUT, PHP_EOL);
        } else {
            $input = fgets(STDIN);
        }
        
        return rtrim($input);
    }
    
    /**
     * read password from file
     * 
     * @param string $_filename
     * @return string
     */
    public static function getPasswordFromFile($_filename)
    {
        $result = @file_get_contents($_filename);
        return rtrim($result);
    }
}
