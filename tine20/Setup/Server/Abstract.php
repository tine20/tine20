<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Abstract.php 5110 2008-10-24 15:14:54Z p.schuele@metaways.de $
 * 
 */

/**
 * Abstract Server class with handle and _initFramework functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Setup_Server_Abstract
{
    /**
     * init setup framework
     *
     */
    protected function _initFramework()
    {
        
        Setup_Core::setupConfig();
                
        // Server Timezone must be setup before logger, as logger has timehandling!
        Setup_Core::setupServerTimezone();
        
        Setup_Core::setupLogger();

        Setup_Core::setupSession();
        
        // setup a temporary user locale/timezone. This will be overwritten later but we 
        // need to handle exceptions during initialisation process such as seesion timeout
        Setup_Core::set('locale', new Zend_Locale('en_US'));
        Setup_Core::set('userTimeZone', 'UTC');
        
        #Tinebase_Core::setupMailer();

        Setup_Core::setupDatabaseConnection();

        //Setup_Core::setupUserTimezone();
        
        Setup_Core::setupUserLocale();
        
        // don't use cache for setup. It breaks when we need to run setup multiple times
        //Setup_Core::setupCache();
        
        header('X-API: http://www.tine20.org/apidocs/tine20/');
    }
    
    
    /**
     * handler for tine requests
     * 
     * @return boolean
     */
    abstract public function handle();
}
