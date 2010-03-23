<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Abstract Server class with handle and _initFramework functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Tinebase_Server_Abstract
{
    /**
     * init tine framework
     *
     */
    protected function _initFramework()
    {
        Tinebase_Core::setupConfig();
        
        Tinebase_Core::setupTempDir();
        
        // Server Timezone must be setup before logger, as logger has timehandling!
        Tinebase_Core::setupServerTimezone();
        
        Tinebase_Core::setupLogger();
        
        // Database Connection must be setup before cache because setupCache uses constant "SQL_TABLE_PREFIX" 
        Tinebase_Core::setupDatabaseConnection();
        
        //Cache must be setup before User Locale because otherwise Zend_Locale tries to setup 
        //its own cache handler which might result in a open_basedir restriction depending on the php.ini settings
        Tinebase_Core::setupCache();

        Tinebase_Core::setupSession();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as session timeout
        // @todo add fallback locale to config file
        Tinebase_Core::set('locale', new Zend_Locale('en_US'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        
        Tinebase_Core::setupMailer();
        
        Tinebase_Core::setupUserCredentialCache();
        
        Tinebase_Core::setupUserTimezone();
        
        Tinebase_Core::setupUserLocale();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * handler for tine requests
     * 
     * @return boolean
     */
    abstract public function handle();
}
