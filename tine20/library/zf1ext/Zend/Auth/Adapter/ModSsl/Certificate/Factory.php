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
 */
class Zend_Auth_Adapter_ModSsl_Certificate_Factory
{
    const TYPE_APACHE    = 'Apache';
    const TYPE_X509      = 'X509';
    const TYPE_ICPBRASIL = 'ICPBrasil';
    
    /**
     * 
     * @param unknown $factory
     */
    static function factory($options)
    {
        foreach (array(null, 'REDIRECT_', 'REDIRECT_REDIRECT_') as $prefix) {
            if (isset($_SERVER[$prefix . 'SSL_CLIENT_VERIFY'])) {
                $prefixLength = strlen($prefix);
                
                $certificate = array();
                
                foreach ($_SERVER as $key => $value) {
                    if (strpos($key, $prefix . 'SSL_CLIENT') === 0) {
                        $certificate[substr($key, $prefixLength)] = $value;
                    }
                }
                
                break;
            }
        }

        if (!isset($certificate)) {
            throw new Zend_Auth_Exception('No SSL variables found!');
        }
                
        switch ($options['validation']) {
            case self::TYPE_APACHE:
                return new Zend_Auth_Adapter_ModSsl_Certificate_Apache($options, $certificate);
                
            case self::TYPE_ICPBRASIL:
                return new Zend_Auth_Adapter_ModSsl_Certificate_X509($options, $certificate);
                
            case self::TYPE_X509:
                return new Zend_Auth_Adapter_ModSsl_Certificate_X509($options, $certificate);
                
            default:
                throw new Zend_Auth_Exception('Unsupported certificate type!');
        } 
    }
}