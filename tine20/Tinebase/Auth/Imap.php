<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
        $this->setOptions($options);
        if ($username !== null) {
            $this->setIdentity($username);
        }
        if ($password !== null) {
            $this->setCredential($password);
        }
    }
    
    /**
     * authenticate() - defined by Zend_Auth_Adapter_Interface.
     *
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to authenticate '. $this->getUsername());
        
        $result = parent::authenticate();
        
        if ($result->isValid()) {
            // username and password are correct, let's do some additional tests            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->getUsername() . ' succeeded');
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Authentication of '. $this->getUsername() . ' failed');
        }
        
        return $result;
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
