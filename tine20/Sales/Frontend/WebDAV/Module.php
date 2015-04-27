<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle webdav requests for Sales modules
 * 
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_WebDAV_Module extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void
     */
    public function createDirectory($name)
    {
        throw new \Sabre\DAV\Exception\Forbidden('Forbidden to create folders here');
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($name)
    {
        switch ($name) {
            case 'Import':
                return new Sales_Frontend_WebDAV_Import($this->_path . '/' . $name);
                
                break;
                
            default:
                throw new Sabre\DAV\Exception\NotFound("Directory $this->_path/$name not found");
                
                break;
        }
    }
    
    public function getChildren()
    {
        return array(
            $this->getChild('Import')
        );
    }
}
