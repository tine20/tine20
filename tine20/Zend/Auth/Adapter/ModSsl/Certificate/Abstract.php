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
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @author     Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author     Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright  Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle X509 certificates
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 */
abstract class Zend_Auth_Adapter_ModSsl_Certificate_Abstract implements Zend_Auth_Adapter_ModSsl_Certificate_Interface
{
    /**
     * The array of arrays of Zend_Ldap options passed to the constructor.
     *
     * @var array
     */
    protected $_options = array();
    
    protected $_certificate = null;
    
    /**
     * 
     * @param array $options
     * @param unknown $certificate
     */
    public function __construct(array $options = array(), $certificate)
    {
        $this->setOptions($options);
        
        $this->setCertificate($certificate);
    }
    
    public function getCertificate()
    {
        return $this->_certificate;
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
     * (non-PHPdoc)
     * @see Zend_Auth_Adapter_ModSsl_Certificate_Interface::setCertificate()
     */
    public function setCertificate($certificate)
    {
        $this->_certificate = $certificate;
        
        return $this;
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
}