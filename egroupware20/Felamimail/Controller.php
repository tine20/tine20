<?php
/**
 * controller for Felamimail
 *
 * @package     FeLaMiMail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Controller
{
	/**
	 * returns list of all configured accounts
	 *
	 * @return array list of configured accounts
	 */
	public function getListOfAccounts() 
	{
	    $accounts = array();
	    
		$config = new Zend_Config_Ini('../../config.ini', 'felamimail');

		foreach($config as $id => $account) {
            $accounts[$id] = $account;
		}
		
		return $accounts;
	}	
}
