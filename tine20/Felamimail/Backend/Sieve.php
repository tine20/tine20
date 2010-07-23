<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * Felamimail Sieve backend
 *
 * @package     Felamimail
 * @subpackage  Backend
 */
class Felamimail_Backend_Sieve extends Zend_Mail_Protocol_Sieve
{
    /**
     * Public constructor
     *
     * @param  array $_config sieve config (host/port/ssl/username/password)
     */
    public function __construct($_config)
    {
        $_config['port'] = (array_key_exists('port', $_config)) ? $_config['port'] : NULL;
        $_config['ssl'] = (array_key_exists('ssl', $_config)) ? $_config['ssl'] : FALSE;
        
        parent::__construct($_config['host'], $_config['port'], $_config['ssl']);
        
        $this->authenticate($_config['username'], $_config['password']);
    }
}
