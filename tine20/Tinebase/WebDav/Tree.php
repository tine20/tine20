<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav tree
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Tree extends Sabre_DAV_Tree 
{
    /**
     * Base url of the Tine 2.0 tree
     *
     * @var string 
     */
    protected $_basePath;

    /**
     * the constructor
     *
     * @param string $_basePath 
     */
    public function __construct($_basePath) 
    {
        $this->_basePath = $_basePath;
    }
    
    /**
     * Returns a new node for the given path 
     * 
     * @param string $path 
     * @return void
     */
    public function getNodeForPath($_path) 
    {
        $pathParts = explode('/', $_path, 2);
        
        list($prefix, $applicationPath) = array($pathParts[0], isset($pathParts[1]) ? $pathParts[1] : null);
        
        $node = new Tinebase_WebDav_Root($prefix);
        
        if ($applicationPath !== null) {
            $node = $node->getChild($applicationPath);
        }
        
        return $node;
        
        $realPath = $this->getRealPath($path);
        if (!file_exists($realPath)) throw new Sabre_DAV_Exception_FileNotFound('File at location ' . $realPath . ' not found');
        if (is_dir($realPath)) { 
            return new Sabre_DAV_FS_Directory($path);
        } else {
            return new Sabre_DAV_FS_File($path);
        }

    }
}