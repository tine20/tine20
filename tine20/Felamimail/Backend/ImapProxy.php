<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Felamimail IMAP connection proxy backend
 * -> checks if imap connection is still active (relogin if not) and call function in Felamimail_Backend_Imap
 *
 * @package     Felamimail
 * @subpackage  Backend
 */
class Felamimail_Backend_ImapProxy
{
    /**
     * imap backend
     * @var Felamimail_Backend_Imap
     */
    protected $_backend;

    /**
     * params for imap connection/login
     * 
     * @var object
     */
    private $_params = array();
    
    /**
     * the constructor
     * 
     * creates instance of Felamimail_Backend_Imap with parameters
     * Supported parameters are
     *   - user username
     *   - host hostname or ip address of IMAP server [optional, default = 'localhost']
     *   - password password for user 'username' [optional, default = '']
     *   - port port for IMAP server [optional, default = 110]
     *   - ssl 'SSL' or 'TLS' for secure sockets
     *   - folder select this folder [optional, default = 'INBOX']
     *
     * @param  array $params mail reader specific parameters
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @return void
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            $params = (object)$params;
        }

        if (!isset($params->user)) {
            throw new Felamimail_Exception_IMAPInvalidCredentials('Need at least user in params.');
        }
        
        $params->host     = isset($params->host)     ? $params->host     : 'localhost';
        $params->password = isset($params->password) ? $params->password : '';
        $params->port     = isset($params->port)     ? $params->port     : null;
        $params->ssl      = isset($params->ssl)      ? $params->ssl      : false;

        $this->_params = $params;
        $this->_backend = new Felamimail_Backend_Imap($params);
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
        try {
            $this->_backend->noop();
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Lost IMAP connection ... trying relogin. (' . $zmpe->getMessage() . ')');
            $this->_backend->connectAndLogin($this->_params);
        }
        
        return call_user_func_array(array($this->_backend, $_name), $_arguments);
    }
}
