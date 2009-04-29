<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        get imap connection config from imap account table
 * @todo        add support for multiple accounts per user
 * @todo        add more common felamimail controller functions (i.e. init imap/cache backends) here
 */

/**
 * folder controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
abstract class Felamimail_Controller_Abstract extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * the current imap connection
     * 
     * @var array of Felamimail_Backend_Imap
     */
    protected $_imapBackends = array();
        
    /**
     * init imap connection
     *
     * @param array $_config
     * @return Felamimail_Backend_Imap
     * 
     * @todo get matching config for given backendId
     * @todo add something like 'useCustomCredentials' to use config imap settings instead of account settings from db
     * @todo do we need a backend for each folder on the mailserver?
     * 
     */
    protected function _getImapBackend($_backendId = 'default')
    {
        if (!isset($this->_imapBackends[$_backendId])) {
            // we need to instantiate a new imap backend
            $imapConfig = Tinebase_Core::getConfig()->imap;
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Connecting to server ' . $imapConfig->host . ':' . $imapConfig->port . ' with username ' . $imapConfig->user);
            
            $this->_imapBackends[$_backendId] = new Felamimail_Backend_Imap($imapConfig->toArray());
        }
        
        return $this->_imapBackends[$_backendId];
    }
}
