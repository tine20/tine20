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
                
        // Server Timezone must be setup before logger, as logger has timehandling!
        Tinebase_Core::setupServerTimezone();
        
        Tinebase_Core::setupLogger();

        Tinebase_Core::setupSession();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as seesion timeout
        // @todo add fallback locale to config.ini
        Tinebase_Core::set('locale', new Zend_Locale('en_US'));
        Tinebase_Core::set('userTimeZone', 'UTC');
        
        Tinebase_Core::setupMailer();

        Tinebase_Core::setupDatabaseConnection();

        Tinebase_Core::setupUserTimezone();
        
        Tinebase_Core::setupUserLocale();
        
        Tinebase_Core::setupCache();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    /**
     * handler for tine requests
     * 
     * @return boolean
     */
    abstract public function handle();
}
