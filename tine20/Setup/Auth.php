<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * main authentication class
 * 
 * @package     Setup
 * @subpackage  Auth 
 */

class Setup_Auth implements Zend_Auth_Adapter_Interface
{
    /**
     * The username of the account being authenticated.
     *
     * @var string
     */
    protected $_username = null;

    /**
     * The password of the account being authenticated.
     *
     * @var string
     */
    protected $_password = null;

    /**
     * Sets username and password for authentication
     *
     * @param $username
     * 
     */
    public function __construct($_username, $_password)
    {
        $this->_username = $_username;
        $this->_password = $_password;
    }
    
    /**
     * authenticate user
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (isset(Setup_Core::get(Setup_Core::CONFIG)->setupuser)) {
            $setupConfig = Setup_Core::get(Setup_Core::CONFIG)->setupuser;
            
            $givenPassword = self::isMd5($setupConfig->password) ? md5($this->_password) : $this->_password;
            
            if ($setupConfig->username == $this->_username && $setupConfig->password == $givenPassword) {
                $code = Zend_Auth_Result::SUCCESS;
                $messages = array('Login successful');
            } else {
                #Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . " $setupConfig->username == $this->_username && $setupConfig->password == $this->_password ");
                
                $code = Zend_Auth_Result::FAILURE;
                $messages = array('Login failed');
            }
        } else {
            $code = Zend_Auth_Result::FAILURE;
            $messages = array('No setup user found in config.inc.php');
        }
                
        $result = new Zend_Auth_Result(
            $code,
            $this->_username,
            $messages
        );
        
        return $result;
    }
    
    /**
     * Check if the given {@param $_string} is a md5 hash or not
     *  
     * @param String $_string
     * @return bool
     */
    public static function isMd5($_string)
    {
        return preg_match('/^[A-Fa-f0-9]{32}$/', $_string);
    }
}
