<?php
/**
 * Tine 2.0
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for filemanager
 * 
 * @package     MailFiler
 */
class MailFiler_Frontend_WebDAV_Directory extends Tinebase_Frontend_WebDAV_Directory
{
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'MailFiler_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'MailFiler_Frontend_WebDAV_Directory';
}
