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
 * class to handle webdav requests for OnlyOfficeIntegrator
 * 
 * @package     OnlyOfficeIntegrator
 */
class OnlyOfficeIntegrator_Frontend_WebDAV_Directory extends Tinebase_Frontend_WebDAV_Directory
{
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
