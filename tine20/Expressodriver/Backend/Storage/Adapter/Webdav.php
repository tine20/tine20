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
 * Webdav adapter class
 */
class Expressodriver_Backend_Storage_Adapter_Webdav extends Expressodriver_Backend_Storage_Abstract implements Expressodriver_Backend_Storage_Adapter_Interface, Expressodriver_Backend_Storage_Capabilities
{
    /**
     * Constant for Expresso drive cache key
     */
    const GETEXPRESSODRIVEETAGS = 'getExpressodriveEtags';

    /**
     * Constant for Expresso drive cache entry tag
     */
    const EXPRESSODRIVEETAGS = 'expressodriverEtags';

    /**
     * @var string password
     */
    protected $password;

    /**
     * @var string user name
     */
    protected $user;

    /**
     * @var string host
     */
    protected $host;

    /**
     * @var boolean is use https of host
     */
    protected $secure;

    /**
     * @var string root folder
     */
    protected $root;

    /**
     * @var path of certificates
     */
    protected $certPath;

    /**
     * @var boolean is ready
     */
    protected $ready;

    /**
     * @var string adapter name
     */
    protected $name;

    /**
     * @var \Sabre\DAV\Client
     */
    private $client;

    /**
     * @var array of files
     */
    private static $tempFiles = array();

    /**
     * @var boolean use cache for folder and files metadata
     */
    private $useCache = true;

    /**
     * @var integer cache lifetime in milisecs
     */
    private $cacheLifetime = 86400; // one day

    /**
     * @var string user locale/timezone
     */
    private $timezone;

    /**
     * the constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['host']) && isset($options['user']) && isset($options['password'])) {
            $host = $options['host'];
            if (substr($host, 0, 8) == "https://")
                $host = substr($host, 8);
            else if (substr($host, 0, 7) == "http://")
                $host = substr($host, 7);
            $this->host = $host;
            $this->user = $options['user'];
            $this->password = $options['password'];
            $this->name = $options['name'];
            if (isset($options['secure'])) {
                if (is_string($options['secure'])) {
                    $this->secure = ($options['secure'] === 'true');
                } else {
                    $this->secure = (bool) $options['secure'];
                }
            } else {
                $this->secure = false;
            }

            $this->root = isset($options['root']) ? $options['root'] : '/';
            if (!$this->root || $this->root[0] != '/') {
                $this->root = '/' . $this->root;
            }
            if (substr($this->root, -1, 1) != '/') {
                $this->root .= '/';
            }

            $this->timezone = Tinebase_Core::getUserTimezone();
            $this->useCache = $options['useCache'];
            $this->cacheLifetime = $options['cacheLifetime'];

        } else {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . 'Webdav config error. Please check your Expressodriver settings');
            throw new Exception('Webdav config error. Please check your Expressodriver settings');
        }
    }

    /*
     * Initialize webdav server connection
     */
    private function init()
    {
        if ($this->ready) {
            return;
        }
        $this->ready = true;

        $settings = array(
            'baseUri' => $this->createBaseUri(),
            'userName' => $this->user,
            'password' => $this->password,
        );
        $this->client = new \Sabre\DAV\Client($settings);
        $this->client->setVerifyPeer(false);
    }

    /**
     * verify if file exists
     *
     * @param  string $path path
     * @return boolean of exists file in path
     */
    public function fileExists($path)
    {
        $this->init();
        $cleanPath = $this->cleanPath($path);
        try {
            $this->client->propfind($this->encodePath($cleanPath), array('{DAV:}resourcetype'));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * open file in to path
     *
     * @param  string $_path
     * @param  string $_mode
     */
    public function fopen($_path, $_mode)
    {
        $this->init();
        $path = $this->cleanPath($_path);
        switch ($_mode) {
            case 'r':
            case 'rb':
                if (!$this->fileExists($path)) {
                    return false;
                }
                //straight up curl instead of sabredav here, sabredav put's the entire get result in memory
                $curl = curl_init();
                $fp = fopen('php://temp', 'r+');
                curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
                curl_setopt($curl, CURLOPT_URL, $this->createBaseUri() . $this->encodePath($path));
                curl_setopt($curl, CURLOPT_FILE, $fp);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                if ($this->secure === true) {
                    // @todo: verify certificates
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                    if ($this->certPath) {
                        curl_setopt($curl, CURLOPT_CAINFO, $this->certPath);
                    }
                }

                curl_exec($curl);
                $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($statusCode !== 200) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' fopen error ' . $path);
                }
                curl_close($curl);
                rewind($fp);
                return $fp;
            case 'w':
            case 'wb':
            case 'a':
            case 'ab':
            case 'r+':
            case 'w+':
            case 'wb+':
            case 'a+':
            case 'x':
            case 'x+':
            case 'c':
            case 'c+':
                //emulate these
                if (strrpos($path, '.') !== false) {
                    $ext = substr($path, strrpos($path, '.'));
                } else {
                    $ext = '';
                }
                if ($this->fileExists($path)) {
                    if (!$this->isUpdatable($path)) {
                        return false;
                    }
                    $tmpFile = $this->getCachedFile($path);
                } else {
                    if (!$this->isCreatable(dirname($path))) {
                        return false;
                    }
                }
                self::$tempFiles[$tmpFile] = $path;
                return fopen('close://' . $tmpFile, $_mode);
        }
    }

    /**
     * return the free space of user folder in webdav
     *
     * @param string $path
     * @return array of quota used and available bytes
     */
    public function freeSpace($path)
    {
        $response = $this->client->propfind($this->encodePath($path), array('{DAV:}quota-available-bytes', '{DAV:}quota-used-bytes'), 0);
        return array(
            'quota-available-bytes' => $response['{DAV:}quota-available-bytes'],
            'quota-used-bytes' => $response['{DAV:}quota-used-bytes']
        );
    }

    /**
     * get content type
     *
     * @param string $path
     * @return string of ContentType
     */
    public function getContentType($path)
    {
        $response = $this->client->propfind($this->encodePath($path), array('{DAV:}getcontenttype'), 0);
        return $response['{DAV:}getcontenttype'];
    }

    /**
     * get ETag from path
     *
     * @param string $path
     * @return string eTag hash
     */
    public function getEtag($path)
    {
        $response = $this->client->propfind($this->encodePath($path), array('{DAV:}getetag'), 0);
        return $response['{DAV:}getetag'];
    }

    /**
     * get the time of last modified path node
     *
     * @param string $path
     * @return Tinebase_DateTime
     */
    public function getMtime($path)
    {
        $response = $this->client->propfind($this->encodePath($path), array('{DAV:}getlastmodified'), 0);
        return Tinebase_DateTime($response['{DAV:}getlastmodified'], $this->timezone);
    }

    /**
     * create folder in webdav
     *
     * @param string $_path
     * @return boolean success
     */
    public function mkdir($_path)
    {
        $this->init();
        $path = $this->cleanPath($_path);
        return $this->simpleResponse('MKCOL', $path, null, 201);
    }


    /**
     * rename folder or file
     *
     * @param string $_oldPath
     * @param string $_newPath
     * @return boolean success
     */
    public function rename($_oldPath, $_newPath)
    {
        $this->init();
        $oldPath = $this->encodePath($this->cleanPath($_oldPath));
        $newPath = $this->createBaseUri() . $this->encodePath($this->cleanPath($_newPath));
        try {
            $this->client->request('MOVE', $oldPath, null, array('Destination' => $newPath));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * remove folder
     *
     * @param string $path
     * @param boolean $recursive
     * @return boolean success
     */
    public function rmdir($path, $recursive = FALSE)
    {
        $this->init();
        $path = $this->cleanPath($path) . '/';
        // FIXME: some WebDAV impl return 403 when trying to DELETE
        // a non-empty folder
        return $this->simpleResponse('DELETE', $path, null, 204);
    }

    /**
     * get node of path
     *
     * @param string $_path
     * @return array of nodes the files and folders
     */
    public function stat($_path)
    {
        $this->init();
        try {
            $response = $this->getNodes($_path);
            if (count($response) > 0) {
                return $response[0];
            } else {
                return array();
            }
        } catch (Exception $ex) {
            return array();
        }
    }

    /**
     * serch files and folder of path
     *
     * @param string $query
     * @param string $_path
     * @return nodes of files and folders
     */
    public function search($query, $_path = '')
    {
        $this->init();
        try {
            $result = $this->getNodes($_path);
            array_shift($result); //the first entry is the current directory
            $trimQuery = trim($query);
            if (!empty($trimQuery)) { // filter query
                $resultFilter = array();
                foreach ($result as $file) {
                    if (strstr(strtolower($file['name']), strtolower($query)) !== false || empty($query)) {
                        $resultFilter[] = $file;
                    }
                }
                $result = $resultFilter;
            }
            return $result; //$response;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * delete the file or folder in server
     *
     * @param string $path
     * @return boolean if successful
     */
    public function unlink($path)
    {
        $this->init();
        return $this->simpleResponse('DELETE', $path, null, 204);
    }


    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
        );
    }

    /**
     * create base URL from config
     *
     * @return string url of server webdav
     */
    protected function createBaseUri()
    {
        $baseUri = 'http';
        if ($this->secure) {
            $baseUri .= 's';
        }
        $baseUri .= '://' . $this->host . $this->root;
        return $baseUri;
    }

    /**
     * upload file to path
     *
     * @param  string $path
     * @param  string $target
     */
    public function uploadFile($path, $target)
    {
        $this->init();
        $source = fopen($path, 'r');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($curl, CURLOPT_URL, $this->createBaseUri() . $this->encodePath($target));
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curl, CURLOPT_INFILE, $source); // file pointer
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($path));
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($this->secure === true) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            if ($this->certPath) {
                curl_setopt($curl, CURLOPT_CAINFO, $this->certPath);
            }
        }
        curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode !== 200) {
            error_log("webdav client", 'curl GET ' . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) . ' returned status code ' . $statusCode);
        }
        curl_close($curl);
        fclose($source);
    }

    /**
     * parse permission of string
     *
     * @param string $_permissionsString
     * @return array of permissions
     */
    protected function parsePermissions($_permissionsString)
    {
        $permissions = array(parent::PERMISSION_READ => true);
        if (strpos($_permissionsString, 'R') !== false) {
            $permissions = array_merge($permissions, array(parent::PERMISSION_SHARE => true));
        }
        if (strpos($_permissionsString, 'D') !== false) {
            $permissions = array_merge($permissions, array(parent::PERMISSION_DELETE => true));
        }
        if (strpos($_permissionsString, 'W') !== false) {
            $permissions = array_merge($permissions, array(parent::PERMISSION_UPDATE => true));
        }
        if (strpos($_permissionsString, 'CK') !== false) {
            $permissions = array_merge($permissions, array(parent::PERMISSION_CREATE => true, parent::PERMISSION_UPDATE => true));
        }
        return $permissions;
    }

    /**
     * verify update permission
     *
     * @param string $_path
     * @return boolean of permission
     */
    public function isUpdatable($_path)
    {
        return (bool) ($this->getPermissions($_path) & parent::PERMISSION_UPDATE);
    }

    /**
     * verify creatable permission
     *
     * @param string $_path
     * @return boolean of permission
     */
    public function isCreatable($_path)
    {
        return (bool) ($this->getPermissions($_path) & parent::PERMISSION_CREATE);
    }

    /**
     * verify sharable permission
     *
     * @param string $_path
     * @return boolean of permission
     */
    public function isSharable($_path)
    {
        return (bool) ($this->getPermissions($_path) & parent::PERMISSION_SHARE);
    }

    /**
     * verify delete permission
     *
     * @param string $_path
     * @return boolean of permission
     */
    public function isDeletable($_path)
    {
        return (bool) ($this->getPermissions($_path) & parent::PERMISSION_DELETE);
    }

    /**
     * get grants of node
     *
     * @param node $_node
     * @return array of permission
     */
    private function getGrants($_node)
    {
        if (isset($_node['{http://owncloud.org/ns}permissions'])) {
            return $this->parsePermissions($_node['{http://owncloud.org/ns}permissions']);
        } else if (!isset($_node['{DAV:}getcontenttype'])) { // folder
            return array('readGrant' => true, 'addGrant' => true, 'editGrant' => true, 'deleteGrant' => true, 'shareGrant' => true);
        } else if (isset($_node['{DAV:}getcontenttype'])) { // file
            return array('readGrant' => true, 'editGrant' => true, 'deleteGrant' => true, 'shareGrant' => true);
        } else {
            return array();
        }
    }

    /**
     * get permission of files and folders
     *
     * @param string $_path
     * @return array of [permission]
     */
    public function getPermissions($_path)
    {
        $this->init();
        $path = $this->cleanPath($_path);
        $response = $this->client->propfind($this->encodePath($path), array('{http://owncloud.org/ns}permissions'));
        if (isset($response['{http://owncloud.org/ns}permissions'])) {
            return $this->parsePermissions($response['{http://owncloud.org/ns}permissions']);
        } else if ($this->isDir($path)) {
            return array('readGrant' => true, 'addGrant' => true, 'editGrant' => true, 'deleteGrant' => true, 'shareGrant' => true);
        } else if ($this->fileExists($path)) {
            return array('readGrant' => true, 'editGrant' => true, 'deleteGrant' => true, 'shareGrant' => true);
        } else {
            return 0;
        }
    }

    /**
     * request method of server webdav
     *
     * @param string $method
     * @param string $path
     * @param integer $expected status code
     * @return boolean request successful
     */
    private function simpleResponse($_method, $_path, $_body, $_expected)
    {
        $path = $this->cleanPath($_path);
        try {
            $response = $this->client->request($_method, $this->encodePath($path), $_body);
            return $response['statusCode'] == $_expected;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * URL encodes the given path but keeps the slashes
     *
     * @param string $path to encode
     * @return string encoded path
     */
    private function encodePath($path)
    {
        // slashes need to stay
        return str_replace('%2F', '/', rawurlencode($path));
    }

    /**
     * check if curl is installed
     * @return boolean true or [curl] if not exists
     */
    public static function checkDependencies()
    {
        if (function_exists('curl_init')) {
            return true;
        } else {
            return array('curl');
        }
    }

    /**
     * get filetype of the path node
     *
     * @param string $path
     * @return string dir or file
     */
    public function filetype($path)
    {
        $this->init();
        $_path = $this->cleanPath($path);
        try {
            $response = $this->client->propfind($this->encodePath($_path), array('{DAV:}resourcetype'));
            $responseType = array();
            if (isset($response["{DAV:}resourcetype"])) {
                $responseType = $response["{DAV:}resourcetype"]->resourceType;
            }
            return (count($responseType) > 0 and $responseType[0] == "{DAV:}collection") ? 'dir' : 'file';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * clean and remove unwanted slash of a path
     *
     * @param string $path
     * @return string path
     */
    public function cleanPath($path)
    {
        $_path = Expressodriver_Backend_Storage_Abstract::normalizePath($path);
        // remove leading slash
        return substr($_path, 1);
    }

    /**
     * get nodes of a path
     * NOTE: getNodes returns path root node and its childreen:
     * [0]          -> path root node (used by stat)
     * [1] .. [N]   -> childreen nodes (used by search)
     *
     * @param string $path path
     * @return array of nodes
     */
    private function getNodes($path)
    {
        $_path = $this->cleanPath($path);
        if ($this->useCache) {
            $response = $this->client->propfind($this->encodePath($_path), array('{DAV:}getetag'), 0);
            $result = $this->getNodesFromCache($_path, $response['{DAV:}getetag']);
        } else {
            $result = $this->getNodesFromBackend($_path);
        }
        return $result;
    }

    /**
     * get nodes from cache
     * if cache miss or cache etag is outdated, updates cache with nodes from backend
     *
     * @param string $path path
     * @param string $etag hash etag
     * @return array of nodes
     */
    private function getNodesFromCache($path, $etag)
    {
        $cache = Tinebase_Core::get('cache');
        $cacheId = Tinebase_Helper::arrayToCacheId(
                array(
                    self::GETEXPRESSODRIVEETAGS,
                    sha1(Tinebase_getUser()->getId()) . $this->encodePath($path)
                )
            );
        $result = $cache->load($cacheId);
        if (!$result) {
            $result = $this->getNodesFromBackend($path);
            $cache->save($result, $cacheId, array(self::EXPRESSODRIVEETAGS), $this->cacheLifetime);
        } else {
            if ($result[0]['hash'] != $etag) {
                $result = $this->getNodesFromBackend($path);
                $cache->save($result, $cacheId, array(self::EXPRESSODRIVEETAGS), $this->cacheLifetime);
            }
        }
        return $result;
    }

    /**
     * get nodes from adapter backend
     *
     * @param string $path
     * @return array nodes
     */
    private function getNodesFromBackend($path)
    {
        $response = $this->client->propfind($this->encodePath($path), array(), 1);
        $result = array();
        $statNode = true;
        $path = $path === false ? '' : $path;
        foreach ($response as $key => $value) {
            if($statNode) {
                $nodePath = $path;
                $statNode = false;
            } else {
                $nodePath = $path . '/' . urldecode(basename($key));
            }
            $result[] = $this->rawDataToNode($nodePath, $value);
        }
        return $result;
    }

    /**
     * converts raw data from adapter into a node array
     *
     * @param string $path
     * @param array $file
     * @return array of nodes
     */
    private function rawDataToNode($path, $file)
    {
        $filetmp = array(
            'name' => urldecode(basename($path)),
            'path' => $path,
            'hash' => $file['{DAV:}getetag'],
            'last_modified_time' => new Tinebase_DateTime($file['{DAV:}getlastmodified'], $this->timezone),
            'size' => $file['{DAV:}getcontentlength'],
            'type' => isset($file['{DAV:}getcontenttype']) ? Tinebase_Model_Tree_Node::TYPE_FILE : Tinebase_Model_Tree_Node::TYPE_FOLDER,
            'resourcetype' => $file['{DAV:}resourcetype'],
            'contenttype' => $file['{DAV:}getcontenttype'],
            'account_grants' => $this->getGrants($file)
        );
        return $filetmp;
    }

    /**
     * check if webdav credentials are valid
     *
     * @param string $url
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function checkCredentials($url, $username, $password)
    {
        $arr = explode('://', $url, 2);
        list($webdavauth_protocol, $webdavauth_url_path) = $arr;
        $url = $webdavauth_protocol.'://'.urlencode($username).':'.urlencode($password).'@'.$webdavauth_url_path;

        $headers = get_headers($url);
        if ($headers == false) {
            return false;
        }
        $returncode = substr($headers[0], 9, 3);

        return substr($returncode, 0, 1) === '2';
    }
}