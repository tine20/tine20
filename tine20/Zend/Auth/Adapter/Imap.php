<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Ldap.php 10020 2009-08-18 14:34:09Z j.fischer@metaways.de $
 */

/**
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Auth_Adapter_Imap implements Zend_Auth_Adapter_Interface
{

    /**
     * The Zend_Mail_Protocol_Imap context.
     *
     * @var Zend_Mail_Protocol_Imap
     */
    protected $_imap = null;

    /**
     * The array of arrays of Zend_Ldap options passed to the constructor.
     *
     * @var array
     */
    protected $_options = null;

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
     * Constructor
     *
     * @param  array  $options  An array of arrays of Zend_Ldap options
     * @param  string $username The username of the account being authenticated
     * @param  string $password The password of the account being authenticated
     * @return void
     */
    public function __construct(array $options = array(), $username = null, $password = null)
    {
        $this->setOptions($options);
        if ($username !== null) {
            $this->setUsername($username);
        }
        if ($password !== null) {
            $this->setPassword($password);
        }
    }

    /**
     * Returns the array of arrays of Zend_Ldap options of this adapter.
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets the array of arrays of Zend_Ldap options to be used by
     * this adapter.
     *
     * @param  array $options The array of arrays of Zend_Ldap options
     * @return Zend_Auth_Adapter_Ldap Provides a fluent interface
     */
    public function setOptions($options)
    {
        $this->_options = is_array($options) ? $options : array();
        return $this;
    }

    /**
     * Returns the username of the account being authenticated, or
     * NULL if none is set.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Sets the username for binding
     *
     * @param  string $username The username for binding
     * @return Zend_Auth_Adapter_Ldap Provides a fluent interface
     */
    public function setUsername($username)
    {
        $this->_username = (string) $username;
        return $this;
    }

    /**
     * Returns the password of the account being authenticated, or
     * NULL if none is set.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Sets the passwort for the account
     *
     * @param  string $password The password of the account being authenticated
     * @return Zend_Auth_Adapter_Ldap Provides a fluent interface
     */
    public function setPassword($password)
    {
        $this->_password = (string) $password;
        return $this;
    }

    /**
     * Returns the IMAP Object
     *
     * @return Zend_Mail_Protocol_Imap The Zend_Mail_Protocol_Imap object used to authenticate the credentials
     */
    public function getImap()
    {
        if ($this->_imap === null) {
            $this->_imap = new Zend_Mail_Protocol_Imap();
        }

        return $this->_imap;
    }

    /**
     * Set an IMAP connection
     *
     * @param Zend_Mail_Protocol_Imap $imao An existing IMAP object
     * @return Zend_Auth_Adapter_Imap Provides a fluent interface
     */
    public function setImap(Zend_Mail_Protocol_Imap $imap)
    {
        $this->_imap = $imap;
//@todo check imap->getOptions()
        $this->setOptions(array($imap->getOptions()));

        return $this;
    }

    /**
     * Authenticate the user
     *
     * @throws Zend_Auth_Adapter_Exception
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $messages = array();
        $messages[0] = ''; // reserved
        $messages[1] = ''; // reserved

        $username = $this->_username;
        $password = $this->_password;

        if (!$username) {
            $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $messages[0] = 'A username is required';
            return new Zend_Auth_Result($code, '', $messages);
        }
        
        if (!$password) {
            /* A password is required because some servers will
             * treat an empty password as an anonymous bind.
             */
            $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $messages[0] = 'A password is required';
            return new Zend_Auth_Result($code, '', $messages);
        }

        $imap = $this->getImap();

        $code = Zend_Auth_Result::FAILURE;
        $messages[0] = "Authority not found: $username";

        $host = isset($this->_options['host']) ? $this->_options['host'] : 'localhost';
        $port = isset($this->_options['port']) ? $this->_options['port'] : null;
        $ssl  = isset($this->_options['ssl'])  ? $this->_options['ssl']  : false;

        $imap->connect($host, $port, $ssl);
        if (!$imap->login($username, $password)) {
            /**
             * @see Zend_Mail_Storage_Exception
             */
            require_once 'Zend/Mail/Storage/Exception.php';
            throw new Zend_Mail_Storage_Exception('cannot login, user or password wrong');
        }
        
        
        /* Iterate through each server and try to authenticate the supplied
         * credentials against it.
         */
        foreach ($this->_options as $name => $options) {

            if (!is_array($options)) {
                /**
                 * @see Zend_Auth_Adapter_Exception
                 */
                require_once 'Zend/Auth/Adapter/Exception.php';
                throw new Zend_Auth_Adapter_Exception('Adapter options array not in array');
            }
            $ldap->setOptions($options);
            $dname = '';

            try {
                if ($messages[1])
                    $messages[] = $messages[1];
                $messages[1] = '';
                $messages[] = $this->_optionsToString($options);

                $dname = $this->_getAuthorityName();
                if (isset($failedAuthorities[$dname])) {
                    /* If multiple sets of server options for the same domain
                     * are supplied, we want to skip redundant authentications
                     * where the identity or credentials where found to be
                     * invalid with another server for the same domain. The
                     * $failedAuthorities array tracks this condition (and also
                     * serves to supply the original error message).
                     * This fixes issue ZF-4093.
                     */
                    $messages[1] = $failedAuthorities[$dname];
                    $messages[] = "Skipping previously failed authority: $dname";
                    continue;
                }

                $canonicalName = $ldap->getCanonicalAccountName($username);

                $ldap->bind($canonicalName, $password);

                $messages[0] = '';
                $messages[1] = '';
                $messages[] = "$canonicalName authentication successful";

                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $canonicalName, $messages);
            } catch (Zend_Ldap_Exception $zle) {

                /* LDAP based authentication is notoriously difficult to diagnose. Therefore
                 * we bend over backwards to capture and record every possible bit of
                 * information when something goes wrong.
                 */

                $err = $zle->getCode();

                if ($err == Zend_Ldap_Exception::LDAP_X_DOMAIN_MISMATCH) {
                    /* This error indicates that the domain supplied in the
                     * username did not match the domains in the server options
                     * and therefore we should just skip to the next set of
                     * server options.
                     */
                    continue;
                } else if ($err == Zend_Ldap_Exception::LDAP_NO_SUCH_OBJECT) {
                    $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
                    $messages[0] = "Account not found: $username";
                    $failedAuthorities[$dname] = $zle->getMessage();
                } else if ($err == Zend_Ldap_Exception::LDAP_INVALID_CREDENTIALS) {
                    $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                    $messages[0] = 'Invalid credentials';
                    $failedAuthorities[$dname] = $zle->getMessage();
                } else {
                    $line = $zle->getLine();
                    $messages[] = $zle->getFile() . "($line): " . $zle->getMessage();
                    $messages[] = str_replace($password, '*****', $zle->getTraceAsString());
                    $messages[0] = 'An unexpected failure occurred';
                }
                $messages[1] = $zle->getMessage();
            }
        }

        $msg = isset($messages[1]) ? $messages[1] : $messages[0];
        $messages[] = "$username authentication failed: $msg";

        return new Zend_Auth_Result($code, $username, $messages);
    }

    /**
     * Converts options to string
     *
     * @param  array $options
     * @return string
     */
    private function _optionsToString(array $options)
    {
        $str = '';
        foreach ($options as $key => $val) {
            if ($key === 'password')
                $val = '*****';
            if ($str)
                $str .= ',';
            $str .= $key . '=' . $val;
        }
        return $str;
    }
}
