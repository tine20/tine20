<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle containers in WebDAV tree
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Frontend
 */
class OnlyOfficeIntegrator_Frontend_WebDAV_Container extends Tinebase_Frontend_WebDAV_Container
{
    /**
    * application name
    *
    * @var string
    */
    protected $_applicationName = OnlyOfficeIntegrator_Config::APP_NAME;
    
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = OnlyOfficeIntegrator_Frontend_WebDAV_File::class;
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = OnlyOfficeIntegrator_Frontend_WebDAV_Directory::class;
}
