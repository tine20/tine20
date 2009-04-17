<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo remove cli session/cache path (add http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/ to helpers?)
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Cli extends Tinebase_Server_Abstract
{
    /**
     * cli session path
     *
     */
    const CLI_SESSION_PATH = '/tmp/tine20_cli_session';
    
    /**
     * init tine framework
     *
     */
    protected function _initFramework()
    {
        $this->_setupCliConfig();
        Tinebase_Core::setupServerTimezone();
        Tinebase_Core::setupLogger();
        Tinebase_Core::setupSession();
        Tinebase_Core::set('locale', new Zend_Locale('en_US'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        Tinebase_Core::setupMailer();
        Tinebase_Core::setupDatabaseConnection();
        Tinebase_Core::setupCache();
        Tinebase_Core::setupUserTimezone();
        Tinebase_Core::setupUserLocale();
    }
    
    /**
     * initializes the config
     * - overwrite session_save_path
     *
     */
    public function _setupCliConfig()
    {
        $configData = include('config.inc.php');
        if($configData === false) {
            die ('central configuration file config.inc.php not found in includepath: ' . get_include_path());
        }
        $configData['session.save_path'] = self::CLI_SESSION_PATH;
        
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

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Is cli request. method: ' . (isset($opts->method) ? $opts->method : 'EMPTY'));

        $tinebaseServer = new Tinebase_Frontend_Cli();
        $tinebaseServer->authenticate($opts->username, $opts->password);
        $result = $tinebaseServer->handle($opts);

        //@todo remove cli session path
        
        return $result;
    }
}
