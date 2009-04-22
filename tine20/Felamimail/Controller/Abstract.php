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
     * @var Felamimail_Backend_Imap
     */
    protected $_imap = NULL;
        
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();

        $this->_imap = new Felamimail_Backend_Imap(Tinebase_Core::getConfig()->imap->toArray());
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
}
