<?php
/**
 * Tine 2.0
 * 
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Expressomail IMAP connection proxy backend
 * -> checks if imap connection is still active (relogin if not) and call function in Expressomail_Backend_Imap
 *
 * @package     Expressomail
 * @subpackage  Backend
 */
class Expressomail_Backend_ImapProxy
{
    /**
     * Default Imap Backend
     */
    const IMAP = 'Imap';

    /**
     * Default Imap backend class name
     */
    const EXPRESSOMAIL_BACKEND_IMAP = 'Expressomail_Backend_Imap';

    /**
     * imap backend
     * @var Expressomail_Backend_Imap
     */
    protected $_backend;

    /**
     * params for imap connection/login
     * 
     * @var object
     */
    private $_params = array();

    /**
     * Stores all availableBackends
     * @var array
     */
    protected static $_availableBackends = array(
        self::IMAP => self::EXPRESSOMAIL_BACKEND_IMAP
    );
    
    /**
     * the constructor
     * 
     * creates instance of Expressomail_Backend_Imap with parameters
     * Supported parameters are
     *   - user username
     *   - host hostname or ip address of IMAP server [optional, default = 'localhost']
     *   - password password for user 'username' [optional, default = '']
     *   - port port for IMAP server [optional, default = 110]
     *   - ssl 'SSL' or 'TLS' for secure sockets
     *   - folder select this folder [optional, default = 'INBOX']
     *
     * @param  array $params mail reader specific parameters
     * @throws Expressomail_Exception_IMAPInvalidCredentials
     * @return void
     */
    public function __construct($params,$_readOnly = FALSE )
    {
        if (is_array($params)) {
            $params = (object)$params;
        }

        if (!isset($params->user)) {
            throw new Expressomail_Exception_IMAPInvalidCredentials('Need at least user in params.');
        }
        
        $params->host     = isset($params->host)     ? $params->host     : 'localhost';
        $params->password = isset($params->password) ? $params->password : '';
        $params->port     = isset($params->port)     ? $params->port     : null;
        $params->ssl      = isset($params->ssl)      ? $params->ssl      : false;

        $this->_params = $params;

        $expressomailConfig = Expressomail_Config::getInstance();
        $imapBackendConfigDefinition = $expressomailConfig->getDefinition(Expressomail_Config::IMAPBACKEND);

        $backendClassName = self::$_availableBackends[$imapBackendConfigDefinition['default']];
        $expressomailSettings = $expressomailConfig->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
        $backendName = isset($expressomailSettings[Expressomail_Config::IMAPBACKEND]) ?
                $expressomailSettings[Expressomail_Config::IMAPBACKEND] :
                $imapBackendConfigDefinition['default'];
        if($backendName != $imapBackendConfigDefinition['default']) {
            if(Tinebase_Helper::checkClassExistence(self::$_availableBackends[$backendName], true) &&
                    Tinebase_Helper::checkSubClassOf(self::$_availableBackends[$backendName], 'Expressomail_Backend_Imap_Interface', true)) {
                $backendClassName = self::$_availableBackends[$backendName];
            }
        }

        $this->_backend = new $backendClassName($params,$_readOnly);
    }
    
    /**
     * route all function calls to the imap backend (try noop first and relogin if fail)
     *
     * @param  string $_name
     * @param  array  $_arguments
     * @return  mixed
     */
    public function __call($_name, $_arguments)
    {
        return call_user_func_array(array($this->_backend, $_name), $_arguments);
    }

    /**
     * Adds a custom backend of imap
     * @param string $backendName
     * @param string $backendClassName
     * @throws Tinebase_Exception_InvalidArgument
     *
     * @todo put this in Tinebase_Plugin_Manager
     */
    public static function addCustomBackend($backendName, $backendClassName)
    {
        if(isset(self::$_availableBackends[$backendName])) {
            Tinebase_Core::getLogger()->err(__METHOD__ . "::" . __LINE__ . ":: Backend with name $backendName already added");
            throw new Tinebase_Exception_InvalidArgument("Backend with name $backendName already added");
        }

        if(in_array($backendClassName, self::$_availableBackends)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . "::" . __LINE__ . ":: Backend class $backendClassName already added");
            throw new Tinebase_Exception_InvalidArgument("Backend class $backendClassName already added");
        }

        self::$_availableBackends[$backendName] = $backendClassName;
    }

    /**
     * Return all available backends
     * @return array
     */
    public static function getAvailableBackends()
    {
        return self::$_availableBackends;
    }
}
