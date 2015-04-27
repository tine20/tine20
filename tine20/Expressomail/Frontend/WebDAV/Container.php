<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in WebDAV tree
 *
 * @package     Expressomail
 * @subpackage  Frontend
 */
class Expressomail_Frontend_WebDAV_Container extends Tinebase_Frontend_WebDAV_Container
{
    protected $_applicationName = 'Expressomail';
    
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'Expressomail_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'Expressomail_Frontend_WebDAV_Directory';
    
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
