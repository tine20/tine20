<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Mário César Kolling <mario.koling@serpro.gov.br>
 */

/**
 * DigitalCertificate authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_ModSsl extends Zend_Auth_Adapter_ModSsl implements Tinebase_Auth_Interface
{
    /**
     * Constructor
     *
     * @param  array  $options  An array of arrays of IMAP options
     * @return void
     */
    public function __construct(array $options = array(), $username = null, $password = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($options, true));
        
        parent::__construct($options, $username, $password);
    }
    
    /**
     * set loginname
     *
     * @param  string  $identity
     * @return Tinebase_Auth_Imap
     */
    public function setIdentity($identity)
    {
        return parent::setUsername($identity);
    }
    
    /**
     * set password
     *
     * @param  string  $credential
     * @return Tinebase_Auth_Imap
     */
    public function setCredential($credential)
    {
        return parent::setPassword($credential);
    }
}
