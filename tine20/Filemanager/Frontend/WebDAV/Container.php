<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in WebDAV tree
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_WebDAV_Container extends Tinebase_Frontend_WebDAV_Container
{
    /**
    * application name
    *
    * @var string
    */
    protected $_applicationName = 'Filemanager';
    
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'Filemanager_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'Filemanager_Frontend_WebDAV_Directory';
}
