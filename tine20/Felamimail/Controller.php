<?php
/**
 * controller for Felamimail
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Controller
{
    protected $accounts = NULL;
    
    /**
     * array to store the current active imap connections
     *
     * @var array
     */
    private $connections = array();
    
	/**
	 * returns list of all configured accounts
	 *
	 * @return array list of configured accounts
	 */
	public function getListOfAccounts() 
	{
	    if($this->accounts === NULL) {
	        $this->getConfiguration();
	    }
		
		return $this->accounts;
	}
	
    public function getAccount($_accountId) 
    {
        if($this->accounts === NULL) {
            $this->getConfiguration();
        }
        
        if(!isset($this->accounts[$_accountId])) {
            throw new Exception('account does not exist');
        } 

        return $this->accounts[$_accountId];
    }
	
	protected function getConfiguration()
	{
        $this->accounts = array();
        
        $config = new Zend_Config_Ini('../../config.ini', 'felamimail');

        foreach($config as $id => $account) {
            $this->accounts[$id] = $account;
        }
	}
	
	protected function getImapConnection($_accountId)
	{
	    $accountData = $this->getAccount($_accountId);
	    
	    if(isset($this->connections[$_accountId])) {
	        return $this->connections[$_accountId];
	    }
	    
	    try {
    	    $mail = new Felamimail_Imap($accountData->toArray());
    	    
    	    $this->connections[$_accountId] = $mail;
    	    
    	    return $this->connections[$_accountId];
	    } catch (Exception $e) {
	        
	    }
	}

	public function getEmailOverview($_accountId, $_folderName, $_filter, $_sort, $_dir, $_limit, $_start)
	{
	    $result = array();
	    
	    $imapConnection = $this->getImapConnection($_accountId);
	    
	    $imapConnection->selectFolder($_folderName);
	    
	    $seen = $imapConnection->search(array('UNSEEN'));
	    
	    $seenMessages = $imapConnection->getSummary(array_slice($seen, $_start, $_limit));
	    
	    foreach($seenMessages as $message) {
/*	        $result[] = array(
	           'uid'      => $message->uid,
	           'subject'  => $message->getHeader('subject'),
               'from'     => $message->getHeader('from'),
               'to'       => $message->getHeader('to'),
               'sent'     => $message->getHeader('date'),
	           'received' => $message->internalDate,
	           'size'     => $message->size
	        ); */
	        $result[] = $message;
	    }
	    
	    return $result;
	}
	
	public function getSubFolder($_accountId, $_folderName)
	{
        $imapConnection = $this->getImapConnection($_accountId);
        
        if(empty($folderName)) {
            $folder = $imapConnection->getFolders('', '%');
        } else {
            $folder = $imapConnection->getFolders($folderName.'/', '%');
        }
	}
}
