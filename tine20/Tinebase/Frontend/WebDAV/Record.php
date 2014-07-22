<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tine records
 * 
 * @package     Tinebase
 */
class Tinebase_Frontend_WebDAV_Record implements Sabre\DAV\ICollection
{
    protected $_record = null;
    protected $_appName = null;
    
    /**
     * the constructor
     * 
     * @param string $_path
     * @throws Sabre\DAV\Exception\NotFound
     */
    public function __construct($path) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Record path: ' . $path);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r(Sabre\DAV\URLUtil::splitPath($path), true));
        
        try {
            list($appModel, $id) = Sabre\DAV\URLUtil::splitPath($path);
            list($appName, $records, $model) = explode('/', $appModel);
            $this->_record = Tinebase_Core::getApplicationInstance($appName, $model)->get($id);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound('Record ' . $path . ' not found');
        }
        
        $this->_appName   = $appName;
        $this->_path      = $path;
    }
    
    /**
     * return list of children
     * @return array list of children (record attachments)
     */
    public function getChildren() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path);
        
        $children = array();
        
        // TODO throw exception?
            
        // Loop through record attachments / record data
//         foreach ($this->_record->attachments as $attachment) {
//             $children[] = $this->getChild($attachment->path);
//         }
        
        return $children;
    }
    
    /**
     * get child by name
     * 
     * @param  string $name
     * @throws Sabre\DAV\Exception\NotFound
     * @return Tinebase_Frontend_WebDAV_File
     */
    public function getChild($path) 
    {
        $basePath = Tinebase_FileSystem::getInstance()->getApplicationBasePath($this->_appName, Tinebase_FileSystem::FOLDER_TYPE_RECORDS);
        $filePath = preg_replace('@^' . $this->_appName . '/' . Tinebase_FileSystem::FOLDER_TYPE_RECORDS . '@', $basePath, $this->_path);
        return new Tinebase_Frontend_WebDAV_File($filePath . '/' . $path);
    }
    
    public function childExists($name) 
    {
        // TODO implement
    }

    /**
     * Creates a new file in the directory
     *
     * @param string $name Name of the file
     * @param resource|string $data Initial payload
     * @return null|string
     */
    function createFile($name, $data = null)
    {
        // TODO throw exception?
    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @return void
     */
    function createDirectory($name)
    {
        // TODO throw exception?
    }

    /**
     * Deleted the current node
     *
     * @return void
     */
    function delete()
    {
        // TODO throw exception?
    }
    
    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     * 
     * @todo DRY (@see Tinebase_Frontend_WebDAV_Node::getName())
     */
    function getName()
    {
        list(, $basename) = Sabre\DAV\URLUtil::splitPath($this->_path);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $basename);
        
        return $basename;
    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    function setName($name)
    {
        // TODO throw exception?
    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     * 
     * @todo DRY (@see Tinebase_Frontend_WebDAV_Node::getName())
     */
    function getLastModified()
    {
        if ($this->_record instanceof Tinebase_Model_Tree_Node) {
            if ($this->_record->last_modified_time instanceof Tinebase_DateTime) {
                $timestamp = $this->_record->last_modified_time->getTimestamp();
            } else {
                $timestamp = $this->_record->creation_time->getTimestamp();
            }
        } else {
            $timestamp = Tinebase_DateTime::now()->getTimestamp();
        }
        
        return $timestamp;
    }
}
