<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * IMAP authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_Imap extends Zend_Auth_Adapter_Imap implements Tinebase_Auth_Interface
{
    /**
     * Constructor
     *
     * @param  array  $options  An array of arrays of IMAP options
     * @return void
     */
    public function __construct(array $options = array(), $username = null, $password = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($options, true));
        
        $this->setOptions($options[0]);
        if ($username !== null) {
            $this->setIdentity($username);
        }
        if ($password !== null) {
            $this->setCredential($password);
        }
    }
    
    /**
     * set loginname
     *
     * @param string $_identity
     * @return Tinebase_Auth_Imap
     */
    public function setIdentity($_identity)
    {
        parent::setUsername($_identity);
        return $this;
    }
    
    /**
     * set password
     *
     * @param string $_credential
     * @return Tinebase_Auth_Imap
     */
    public function setCredential($_credential)
    {
        parent::setPassword($_credential);
        return $this;
    }    
}
