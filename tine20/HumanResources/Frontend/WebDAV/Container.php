<?php
use Sabre\DAV;

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in WebDAV tree
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_WebDAV_Container extends Tinebase_Frontend_WebDAV_Container
{
    protected $_applicationName = 'HumanResources';
    
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'HumanResources_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'HumanResources_Frontend_WebDAV_Directory';
    
    /**
    * Creates a new subdirectory
    *
    * @param string $name
    * @throws Sabre\DAV\Exception\Forbidden
    * @return void
    */
    public function createDirectory($name)
    {
        throw new DAV\Exception\Forbidden('Forbidden to create folders here');
    }
}
