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
 * @copyright  Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright  Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle X509 certificates
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @todo       phpdoc of all methods
 */
class Zend_Auth_Adapter_ModSsl_Certificate_Apache extends Zend_Auth_Adapter_ModSsl_Certificate_Abstract
{
    protected $certificate;
    protected $casfile;
    protected $crlspath;
    protected $serialNumber = null;
    protected $version      = null;
    protected $subject      = null;
    protected $cn           = null;
    protected $issuer       = null;
    protected $issuerCn     = null;
    protected $hash         = null;
    protected $validFrom    = null;
    protected $validTo      = null;
    protected $canSign      = false;
    protected $canEncrypt   = false;
    protected $username     = null;
    protected $ca           = false;
    protected $authorityKeyIdentifier = null;
    protected $crlDistributionPoints  = null;
    protected $authorityInfoAccess    = null;
    protected $status       = array(
                                  'isValid' => false,
                                  'errors'  => array()
                              );
    
    public function getSerialNumber()
    {
        if (!isset($this->serialNumber)) {
            $this->serialNumber = $this->_certificate['SSL_CLIENT_M_SERIAL'];
        }
        
        return $this->serialNumber;
    }

    public function getVersion()
    {
        if (!isset($this->version)) {
            $this->version = $this->_certificate['SSL_CLIENT_M_VERSION'];
        }
        
        return $this->version;
    }

    public function getSubject()
    {
        if (!isset($this->subject)) {
            $this->subject = array(
                'C'             => $this->_certificate['SSL_CLIENT_S_DN_C'],
                'ST'            => $this->_certificate['SSL_CLIENT_S_DN_ST'],
                'L'             => $this->_certificate['SSL_CLIENT_S_DN_L'],
                'O'             => $this->_certificate['SSL_CLIENT_S_DN_O'],
                'OU'            => $this->_certificate['SSL_CLIENT_S_DN_OU'],
                'CN'            => $this->_certificate['SSL_CLIENT_S_DN_CN'],
                'emailAddress'  => $this->_certificate['SSL_CLIENT_S_DN_Email']
                    
            );
        }
        
        return $this->subject;
    }

    public function getCn()
    {
        if (!isset($this->cn)) {
            $this->cn = $this->_certificate['SSL_CLIENT_S_DN_CN'];
        }
        
        return $this->cn;
    }

    public function getIssuer()
    {
        if (!isset($this->issuer)) {
            $this->issuer = array(
                'C'  => $this->_certificate['SSL_CLIENT_I_DN_C'],
                'ST' => $this->_certificate['SSL_CLIENT_I_DN_ST'],
                'L'  => $this->_certificate['SSL_CLIENT_I_DN_L'],
                'O'  => $this->_certificate['SSL_CLIENT_I_DN_O'],
                'OU' => $this->_certificate['SSL_CLIENT_I_DN_OU'],
                'CN' => $this->_certificate['SSL_CLIENT_I_DN_CN']
            );
        }
        
        return $this->issuer;
    }

    public function getIssuerCn()
    {
        if (!isset($this->issuerCn)) {
            $this->issuerCn = $this->_certificate['SSL_CLIENT_I_DN'];
        }
        
        return $this->issuerCn;
    }

    public function getValidFrom()
    {
        if (!isset($this->validFrom)) {
            $this->validFrom    = new Tinebase_DateTime($this->_certificate['SSL_CLIENT_V_START']);
        }
        
        return $this->validFrom;
    }

    public function getValidTo()
    {
        if (!isset($this->validTo)) {
            $this->validTo      = new Tinebase_DateTime($this->_certificate['SSL_CLIENT_V_END']);
        }
        
        return $this->validTo;
    }
      
    public function getUserName()
    {
        if (!isset($this->username)) {
            if ($this->_options['tryUsernameSplit']) {
                $parts = explode('@' , $this->_certificate['SSL_CLIENT_S_DN_Email'], 2);
                $this->username = $parts[0];
            } else {
                $this->username = $this->_certificate['SSL_CLIENT_S_DN_Email'];
            }
        }
        
        return $this->username;
    }

    public function isValid()
    {
        return isset($this->_certificate['SSL_CLIENT_VERIFY']) && $this->_certificate['SSL_CLIENT_VERIFY'] === 'SUCCESS';
    }
    
    public function getStatusErrors()
    {
        return $this->status['errors'];
    }
    
}