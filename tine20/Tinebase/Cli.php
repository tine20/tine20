 <?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Tinebase_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Tinebase';

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     */
    public function authenticate($_username, $_password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Exception $e) {
                //throw new Exception('account ' . $authResult->getIdentity() . ' not found in account storage');
                echo 'account ' . $authResult->getIdentity() . ' not found in account storage'."\n";
                exit();
            }
            
            Zend_Registry::set('currentAccount', $account);

            $ipAddress = '127.0.0.1';
            $account->setLoginTime($ipAddress);
                        
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                'cli call', // session id not available
                $authResult->getIdentity(),
                $ipAddress,
                $authResult->getCode(),
                Zend_Registry::get('currentAccount')
            ); 
        } else {
            echo "Wrong username and/or password.\n";
            exit();            
        }
    }
    
    /**
     * handle request
     *
     * @param Zend_Console_Getopt $_opts
     * 
     * @todo add help option -> every ApplicationName_Cli.php should define a getHelp() function
     */
    public function handle($_opts)
    {
        list($application, $method) = explode('.', $_opts->method);
        $class = $application . '_' . 'Cli';
        
        if (@class_exists($class)) {
            $object = new $class;
            if ($_opts->info) {
                $result = $object->getHelp();
            } else {
                $result = call_user_func(array($object, $method), $_opts);
            }
        } else {
            echo "Class $class does not exist.\n";
            $result = FALSE;
        }
        
        return $result;
    }
}
