<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
    protected static $_anonymousMethods = array(
        'Tinebase.triggerAsyncEvents',
        'Tinebase.executeQueueJob',
        'Tinebase.monitoringCheckDB',
        'Tinebase.monitoringCheckConfig',
        'Tinebase.monitoringCheckCron',
        'Tinebase.monitoringLoginNumber',
    );
    
    /**
     * return anonymous methods
     * 
     * @param string $method
     * @return array
     */
    public static function getAnonymousMethods($method = null)
    {
        $result = self::$_anonymousMethods;
        
        // check if application cli frontend defines its own anonymous methods
        if ($method && strpos($method, '.') !== false) {
            list($application, $cliMethod) = explode('.', $method);
            $class = $application . '_Frontend_Cli';
            if (@class_exists($class)) {
                $object = new $class;
                if (method_exists($object, 'getAnonymousMethods')) {
                    $result = array_merge($result, call_user_func($class . '::getAnonymousMethods' ));
                }
            }
        }
        
        return $result;
    }
    
    /**
     * initializes the config
     * - overwrite session_save_path
     */
    public function _setupCliConfig()
    {
        $configData = @include('config.inc.php');
        if ($configData === false) {
            echo 'UNKNOWN STATUS / CONFIG FILE NOT FOUND (include path: ' . get_include_path() . ")\n";
            exit(3);
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
        $method = $this->getRequestMethod();
        
        if (! in_array($method, array('Tinebase.monitoringCheckDB', 'Tinebase.monitoringCheckConfig'))) {
            Tinebase_Core::initFramework();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                .' Is cli request. method: ' . $method);
        }
        
        $tinebaseServer = new Tinebase_Frontend_Cli();
        
        $opts = Tinebase_Core::get('opts');
        if (! in_array($method, self::getAnonymousMethods($method))) {
            $tinebaseServer->authenticate($opts->username, $opts->password);
        }
        $result = $tinebaseServer->handle($opts);
        
        //@todo remove cli session path
        
        // convert function result to shell return code
        if ($result === NULL || $result === TRUE || ! is_int($result)) {
            $result = 0;
        } else if ($result === FALSE) {
            $result = 1;
        }
        exit($result);
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        $opts = Tinebase_Core::get('opts');
        return (isset($opts->method)) ? $opts->method : NULL;
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
