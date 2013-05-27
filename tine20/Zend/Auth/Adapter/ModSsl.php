<?php
/**
 * Tine 2.0
 * 
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Mário César Kolling <mario.koling@serpro.gov.br>
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
     * Verify if client was verified by apache mod_ssl
     * 
     * @return boolean true if we have all needed mod_ssl server variables
     */
    static function hasModSsl(){
        
        // Get modssl config session
        $config = Tinebase_Config::getInstance()->get('modssl');
        if ($config && !empty($_SERVER['SSL_CLIENT_CERT']) &&  !empty($_SERVER['SSL_CLIENT_VERIFY']) 
                && $_SERVER['SSL_CLIENT_VERIFY'] == 'SUCCESS') {
            return true;
        }
        
        return false;
        
    }
    
    public function authenticate()
    {
        if (self::hasModSsl()) {
            // get Identity
            $certificate = Tinebase_Auth_ModSsl_Certificate_Factory::buildCertificate($_SERVER['SSL_CLIENT_CERT']);
            $config = Tinebase_Config::getInstance()->get('modssl');
            
            if (class_exists($config->username_callback)) {
                $callback = new $config->username_callback($certificate);
            } else { // fallback to default
                $callback = new Tinebase_Auth_ModSsl_UsernameCallback_Abstract($certificate);
            }
            
            $this->setIdentity(call_user_func(array($callback, 'getUsername')));
            $this->setCredential(null);
            
            if ($certificate instanceof Tinebase_Auth_ModSsl_Certificate_X509) {
                if(!$certificate->isValid()) {
                   $lines = '';
                   foreach($certificate->getStatusErrors() as $line) {
                       $lines .= $line . '#';
                   }
                   
                   if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                       Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ModSsl authentication for '. $this->_identity . ' failed: ' . $lines);
                   }
                   
                   return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, $this->_identity, $certificate->getStatusErrors()); 
                }
                
                $messages = array('Authentication Successfull');
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_identity, $messages);
            }
        }
        
        $messages = array('Nope!');
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, '????', $messages);
    }
}
