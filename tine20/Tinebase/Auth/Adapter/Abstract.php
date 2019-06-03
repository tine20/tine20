<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
abstract class Tinebase_Auth_Adapter_Abstract implements Tinebase_Auth_Interface
{
    /**
     * @var array
     */
    protected $_options;

    /**
     * @var string
     */
    protected $_credential;

    /**
     * @var string
     */
    protected $_identity;

    /**
     * Tinebase_Auth_Adapter_Abstract constructor.
     * @param array $options
     * @param string $username
     * @param string $password
     */
    public function __construct($options, $username = null, $password = null)
    {
        $this->_options = $options;
        if ($username) {
            $this->_identity = $username;
        }
        if ($password) {
            $this->_credential = $password;
        }
    }

    /**
     * setIdentity() - set the value to be used as the identity
     *
     * @param  string $value
     * @return Zend_Auth_Adapter_Interface Provides a fluent interface
     */
    public function setIdentity($value)
    {
        $this->_identity = $value;
        return $this;
    }

    /**
     * setCredential() - set the credential value to be used
     *
     * @param  string $credential
     * @return Zend_Auth_Adapter_Interface Provides a fluent interface
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    abstract public function authenticate();

    /**
     * @return bool
     */
    public function supportsAuthByEmail()
    {
        return false;
    }

    /**
     * @return self
     */
    public function getAuthByEmailBackend()
    {
        throw new Tinebase_Exception_NotImplemented('do not call ' . __METHOD__);
    }
}
