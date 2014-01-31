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
interface Zend_Auth_Adapter_ModSsl_Certificate_Interface
{
    /**
     * 
     * @param array $options
     * @param unknown $certificate
     */
    public function __construct(array $options = array(), $certificate);
    
    /**
     * Returns the array of arrays of Zend_Ldap options of this adapter.
     *
     * @return array|null
     */
    public function getOptions();
    
    /**
     * @return mixed
     */
    public function getCertificate();
    
    public function getUserName();
    
    /**
     * @return boolean
     */
    public function isValid();
    
    /**
     * Sets the array of arrays of Zend_Ldap options to be used by
     * this adapter.
     *
     * @param  array $options The array of arrays of Zend_Ldap options
     * @return Zend_Auth_Adapter_Ldap Provides a fluent interface
     */
    public function setOptions($options);
    
    /**
     * 
     * @param mixed $certificate
     */
    public function setCertificate($certificate);
}