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
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * Abstract class for storage adapters
 * Here comes abstract methods implementation for all external storages.
 */
abstract class Expressodriver_Backend_Storage_Abstract
{

    /**
     * space unknown
     *
     * @staticvar string
     */
    const SPACE_UNKNOWN = 'space unknown';

    /**
     * the right to add
     *
     * @staticvar string
     */
    const PERMISSION_CREATE = 'addGrant';

    /**
     * the right to read
     *
     * @staticvar string
     */
    const PERMISSION_READ = 'readGrant';

    /**
     * the right to edit
     *
     * @staticvar string
     */
    const PERMISSION_UPDATE = 'editGrant';

    /**
     * the right to delete
     *
     * @staticvar string
     */
    const PERMISSION_DELETE = 'deleteGrant';

    /**
     * the right to share
     *
     * @staticvar string
     */
    const PERMISSION_SHARE = 'shareGrant';

    /**
     * external adapter backend factory
     *
     * @param string $_adapter
     * @param array $_options
     * @return Expressodriver_Backend_Storage_Abstract
     * @throws Tinebase_Exception_NotImplemented
     */
    static public function factory($_adapter, array $_options)
    {
        $instance = null;
        try {
            $className = 'Expressodriver_Backend_Storage_Adapter_' . ucfirst($_adapter);
            $instance = new $className($_options);
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . 'storage adapter not implemented');
            throw new Tinebase_Exception_NotImplemented('Storage adapter not implemented '.$e->getMessage());
        }
        return $instance;
    }

    /**
     * return if path is folder
     *
     * @param string $path path
     * @return boolean is folder
     */
    public function isDir($path)
    {
        return $this->filetype($path) == 'dir';
    }

   /**
     * return if path is file
     *
     * @param string $path path
     * @return boolean is file
     */
    public function isFile($path)
    {
        return $this->filetype($path) == 'file';
    }

    /**
     * search tree node in server
     *
     * @param string $query query of search
     * @param string $dir folder
     */
    public function search($query, $dir = '')
    {
        $files = array();
        $dh = $this->opendir($dir);
        if (is_resource($dh)) {
            while (($item = readdir($dh)) !== false) {
                if ($item == '.' || $item == '..')
                    continue;
                if (strstr(strtolower($item), strtolower($query)) !== false || empty($query)) {
                    $files[] = $dir . '/' . $item;
                }
                if ($this->isDir($dir . '/' . $item)) {
                    $files = array_merge($files, $this->search($query, $dir . '/' . $item));
                }
            }
        }
        return $files;
    }

    /**
     * get adapter name
     *
     * @return string adapter name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @brief Fix common problems with a file path
     * @param string $path
     * @param bool $stripTrailingSlash
     * @return string normalized path
     */
    public static function normalizePath($path, $stripTrailingSlash = true)
    {
        if ($path == '') {
            return '/';
        }
        //no windows style slashes
        $path = str_replace('\\', '/', $path);

        //add leading slash
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        // remove '/./'
        // ugly, but str_replace() can't replace them all in one go
        // as the replacement itself is part of the search string
        // which will only be found during the next iteration
        while (strpos($path, '/./') !== false) {
            $path = str_replace('/./', '/', $path);
        }
        // remove sequences of slashes
        $path = preg_replace('#/{2,}#', '/', $path);

        //remove trailing slash
        if ($stripTrailingSlash and strlen($path) > 1 and substr($path, -1, 1) === '/') {
            $path = substr($path, 0, -1);
        }

        // remove trailing '/.'
        if (substr($path, -2) == '/.') {
            $path = substr($path, 0, -2);
        }

        //normalize unicode if possible
        //$path = Util::normalizeUnicode($path);

        return $path;
    }

}
