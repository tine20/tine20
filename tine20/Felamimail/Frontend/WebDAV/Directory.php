<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class to handle webdav requests for Felamimail
 * 
 * @package     Felamimail
 * @subpackage  Frontend
 */
class Felamimail_Frontend_WebDAV_Directory extends Tinebase_Frontend_WebDAV_Directory
{
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'Felamimail_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'Felamimail_Frontend_WebDAV_Directory';
    
    /**
    * Creates a new subdirectory
    *
    * @param string $name
    * @throws Sabre_DAV_Exception_Forbidden
    * @return void
    */
    public function createDirectory($name)
    {
        throw new Sabre_DAV_Exception_Forbidden('Forbidden to create folders here');
    }
}
