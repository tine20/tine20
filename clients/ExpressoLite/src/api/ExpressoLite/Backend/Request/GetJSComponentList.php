<?php
/**
 * Expresso Lite
 * Returns a list of all available Javascript modules that may
 * be used for debugging
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetJSComponentList extends LiteRequest
{
    /**
     * @var array $blacklist Array of folders that will be skipped during
     * component search
     */
    private $blacklist;

    /**
     * @var array $componentList Array with all JS component paths. This
     * is generated during the request execution
     */
    private $componentList;

    /**
     * Checks if a string ends with a specified suffix
     *
     * @param string $str The string to be verified
     * @param string $what The suffix to be searched
     *
     * @return boolean TRUE if $str ends with suffix, FALSE otherwise
     */
    private function endsWith($str, $what)
    {
        return substr($str, -strlen($what)) === $what;
    }

    /**
     * Checks if a string starts with and uppercase char
     *
     * @param string @str The string to be verified
     *
     * @return boolean TRUE if the string start with an
     * uppercase char, FALSE otherwise
     */
    private function startsWithUppercase($str)
    {
        return ctype_upper(substr($str, 0, 1));
    }

    /**
     * Checks if a file contains a JS component. A file is considered
     * a JS component if it starts with an uppercase char and has the
     * .js suffix
     *
     * @param string @file The file name
     *
     * @return boolean TRUE if the file is a JS component
     */
    private function isJSComponentFile($file)
    {
        return $this->startsWithUppercase($file) && $this->endsWith($file, ".js");
    }

    /**
     * Scans a dir to search it for JS components. Subdirs will be
     * scanned recursively. All JS components are stored in $componentList.
     *
     * @param string $path The path of the folder to be scanned
     * @param string $prefix The prefix to be used in all components found in
     * this folder
     */
    private function processDir($path, $prefix)
    {
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $fileinfo) {
            $filename = $fileinfo->getFilename();
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $this->processDir($fileinfo->getPathname(), $prefix . $filename . '/');
            } else if ($this->isJSComponentFile($filename)) {
                $this->componentList[] = $prefix . substr($filename, 0, strlen($filename)- 3);
            }
        }
    }

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $this->blacklist = array(
                LITE_BASE_DIR.'/accessible',
                LITE_BASE_DIR.'/api',
                LITE_BASE_DIR.'/img'
        ); //folders to be skipped during search

        $this->componentList = array();
        $this->processDir(LITE_BASE_DIR, '');

        return (object) array(
            'components' => $this->componentList
        );
    }

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::allowAccessOnlyWithDebuggerModule
     *
     * @return true.
     */
    public function allowAccessOnlyWithDebuggerModule()
    {
        return true;
    }
}
