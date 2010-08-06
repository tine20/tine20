<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 *
 */

/**
 * IMAP Invalid Credentials Exception
 * 
 * @package     Felamimail
 * @subpackage  Exception
 */
class Felamimail_Exception_IMAPInvalidCredentials extends Felamimail_Exception_IMAP
{
    /**
     * imap account
     * 
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * account user name
     * 
     * @var string
     */
    protected $_username = ''; 
    
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'Invalid IMAP Credentials.', $_code = 912) {
        parent::__construct($_message, $_code);
    }
    
    /**
     * set account
     * 
     * @param Felamimail_Model_Account $_account
     * @return Felamimail_Exception_IMAPInvalidCredentials
     */
    public function setAccount(Felamimail_Model_Account $_account)
    {
       $this->_account = $_account;
       return $this;
    }
    
    /**
     * set username
     * 
     * @param string $_username
     * @return Felamimail_Exception_IMAPInvalidCredentials
     */
    public function setUsername($_username)
    {
       $this->_username = $_username;
       return $this;
    }
    
    /**
     * get exception data (account + username) as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'account'   => ($this->_account !== NULL) ? $this->_account->toArray() : array(),
            'username'  => $this->_username,
        );
    }
    
}
