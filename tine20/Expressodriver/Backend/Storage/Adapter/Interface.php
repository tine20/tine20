<?php

/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 *
 */

/**
 * Interface for storage adapters.
 * Common operations to all storage adapters.
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
interface Expressodriver_Backend_Storage_Adapter_Interface
{

    /**
     * verify if file exists
     *
     * @param  string $path path
     * @return boolean of exists file in path
     */
    public function fileExists($path);

    /**
     * return if path is file
     *
     * @param string $path path
     * @return boolean is file
     */
    public function isFile($path);

    /**
     * return if path is folder
     *
     * @param string $path path
     * @return boolean
     */
    public function isDir($path);

    /**
     * return the free space of user folder in webdav
     *
     * @param string $path
     * @return array of quota used and available bytes
     */
    public function freeSpace($path);

    /**
     * get the time of last modified path node
     *
     * @param string $path
     * @return Tinebase_DateTime
     */
    public function getMtime($path);

    /**
     * get content type
     *
     * @param string $path
     * @return string of ContentType
     */
    public function getContentType($path);

    /**
     * create folder in webdav
     *
     * @param string $path
     * @return boolean create folder success
     */
    public function mkdir($path);

    /**
     * rename folder or file
     *
     * @param string $oldPath old path
     * @param string $newPath new path
     * @return boolean rename folder success
     */
    public function rename($oldPath, $newPath);

    /**
     * remove folder
     *
     * @param string $path path
     * @param boolean $recursive recursive remove
     * @return boolean success remove folder
     */
    public function rmdir($path, $recursive = FALSE);

    /**
     * get ETag from path
     *
     * @param string $path path
     * @return string eTag
     */
    public function getEtag($path);

    /**
     * delete the file or folder in server
     *
     * @param string $path
     * @return boolean success delete
     */
    public function unlink($path);

    /**
     * get node of path
     *
     * @param string $path path
     * @return array of nodes the files and folders
     */
    public function stat($path);

}