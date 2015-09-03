<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @Copyright   Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * Stream Dir Storage class
 */
class Expressodriver_Backend_Storage_StreamDir
{

    /**
     * array of path folders
     *
     * @var array of string
     */
    private static $dirs = array();

    /**
     * name of path
     *
     * @var string
     */
    private $name;

    /**
     * index of folder
     *
     * @var integer
     */
    private $index;

    /**
     * open folder
     *
     * @param string $path path
     * @param array $options
     * @return boolean success
     */
    public function dir_opendir($path, $options)
    {
        $this->name = substr($path, strlen('fakedir://'));
        $this->index = 0;
        if (!isset(self::$dirs[$this->name])) {
            self::$dirs[$this->name] = array();
        }
        return true;
    }

    /**
     * read folder
     *
     * @return boolean|string  filenames
     */
    public function dir_readdir()
    {
        if ($this->index >= count(self::$dirs[$this->name])) {
            return false;
        }
        $filename = self::$dirs[$this->name][$this->index];
        $this->index++;
        return $filename;
    }

    /**
     * clouse folder
     *
     * @return boolean success
     */
    public function dir_closedir()
    {
        $this->name = '';
        return true;
    }

    /**
     * Rewind folder
     *
     * @return boolean success
     */
    public function dir_rewinddir()
    {
        $this->index = 0;
        return true;
    }

    /**
     * Register wrapper
     *
     * @param string $path path
     * @param string $content content path
     */
    public static function register($path, $content)
    {
        self::$dirs[$path] = $content;
    }

}
