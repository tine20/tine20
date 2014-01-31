<?php
/**
 * Tine 2.0
 * 
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @license    http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright  Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright  Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Mário César Kolling <mario.koling@serpro.gov.br>
 */

/**
 * DigitalCertificate authentication backend adapter
 * 
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @todo       get object's config parameters in __construct()
 */
class Zend_Auth_Adapter_ModSsl implements Zend_Auth_Adapter_Interface
{
    /**
     * The array of arrays of Zend_Ldap options passed to the constructor.
     *
     * @var array
     */
    protected $_options = array();
    
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
     * (non-PHPdoc)
     * @see Zend_Auth_Adapter_Interface::authenticate()
     */
    public function authenticate()
    {
        // get certificate
        try {
            $certificate = Zend_Auth_Adapter_ModSsl_Certificate_Factory::factory($this->_options);
        } catch (Zend_Auth_Exception $zae) {
            return new Zend_Auth_Result(
               Zend_Auth_Result::FAILURE, 
               null, 
               array($zae->getMessage())
            );
        }
        
        if (!$certificate->isValid()) {
            // certifcate invalid
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, 
                null, 
                $certificate->getStatusErrors()
            ); 
        }
        
        $this->setUsername($certificate->getUserName());
        
        return new Zend_Auth_Result(
            Zend_Auth_Result::SUCCESS, 
            $this->getUsername(), 
            array('Authentication Successfull')
        );
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
}
