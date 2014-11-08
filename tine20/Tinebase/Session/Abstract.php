<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Guilherme Striquer Bisotto <guilherme.bisotto@serpro.gov.br>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Abstract class for Session and Session Namespaces
 * 
 * @package     Tinebase
 * @subpackage  Session
 */
abstract class Tinebase_Session_Abstract extends Zend_Session_Namespace
{
    /**
     * Default session directory name
     */
    const SESSION_DIR_NAME = 'tine20_sessions';
    
    /**
     * constant for session namespace (tinebase) registry index
     */
    const SESSION = 'session';
    
    protected static $_sessionEnabled = false;
    
    /**
     * get a value from the registry
     *
     */
    protected static function get($index)
    {
        return (Zend_Registry::isRegistered($index)) ? Zend_Registry::get($index) : NULL;
    }
    
    /**
     * set a registry value
     *
     * @return mixed value
     */
    protected static function set($index, $value)
    {
        Zend_Registry::set($index, $value);
    }
    
    /**
     * Create a session namespace or return an existing one
     *
     * @param unknown $_namespace
     * @throws Exception
     * @return Zend_Session_Namespace
     */
    protected static function _getSessionNamespace($_namespace)
    {
        $sessionNamespace = self::get($_namespace);
        
        if ($sessionNamespace == null) {
            try {
                $sessionNamespace = new Zend_Session_Namespace($_namespace);
                self::set($_namespace, $sessionNamespace);
            } catch (Exception $e) {
                self::expireSessionCookie();
                throw $e;
            }
        }
        
        return $sessionNamespace;
    }
    
    /**
     * Zend_Session::sessionExists encapsulation
     *
     * @return boolean
     */
    public static function sessionExists()
    {
        return Zend_Session::sessionExists();
    }
    
    /**
     * Zend_Session::isStarted encapsulation
     *
     * @return boolean
     */
    public static function isStarted()
    {
        return Zend_Session::isStarted();
    }
    
    /**
     * Destroy session and remove cookie
     */
    public static function destroyAndRemoveCookie()
    {
        Zend_Session::destroy(true, true);
    }
    
    /**
     * Destroy session but not remove cookie
     */
    public static function destroyAndMantainCookie()
    {
        Zend_Session::destroy(false, true);
    }
    
    /**
     * Zend_Session::writeClose encapsulation
     *
     * @param string $readonly
     */
    public static function writeClose($readonly = true)
    {
        Zend_Session::writeClose($readonly);
    }
    
    /**
     * Zend_Session::isWritable encapsulation
     *
     * @return boolean
     */
    public static function isWritable()
    {
        return Zend_Session::isWritable();
    }
    
    /**
     * Zend_Session::getId encapsulation
     *
     * @return string
     */
    public static function getId()
    {
        return Zend_Session::getId();
    }
    
    /**
     * Zend_Session::expireSessionCookie encapsulation
     */
    public static function expireSessionCookie()
    {
        Zend_Session::expireSessionCookie();
    }
    
    /**
     * Zend_Session::regenerateId encapsulation
     */
    public static function regenerateId()
    {
       Zend_Session::regenerateId();
    }
    
    /**
     * get session dir string (without PATH_SEP at the end)
     *
     * @return string
     */
    public static function getSessionDir()
    {
        $config = Tinebase_Core::getConfig();
        $sessionDir = ($config->session && $config->session->path)
            ? $config->session->path
            : null;
        
        #####################################
        # LEGACY/COMPATIBILITY: 
        # (1) had to rename session.save_path key to sessiondir because otherwise the
        # generic save config method would interpret the "_" as array key/value seperator
        # (2) moved session config to subgroup 'session'
        if (empty($sessionDir)) {
            foreach (array('session.save_path', 'sessiondir') as $deprecatedSessionDir) {
                $sessionDir = $config->get($deprecatedSessionDir, null);
                if ($sessionDir) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key '{$deprecatedSessionDir}' should be renamed to 'path' and moved to 'session' group.");
                }
            }
        }
        #####################################
        
        if (empty($sessionDir) || !@is_writable($sessionDir)) {
            $sessionDir = session_save_path();
            if (empty($sessionDir) || !@is_writable($sessionDir)) {
                $sessionDir = Tinebase_Core::guessTempDir();
            }
            
            $sessionDirName = self::SESSION_DIR_NAME;
            $sessionDir .= DIRECTORY_SEPARATOR . $sessionDirName;
        }
        
        return $sessionDir;
    }
    
    /**
     * set session backend
     */
    public static function setSessionBackend()
    {
        $config = Tinebase_Core::getConfig();
        $backendType = ($config->session && $config->session->backend) ? ucfirst($config->session->backend) : 'File';
        $maxLifeTime = ($config->session && $config->session->lifetime) ? $config->session->lifetime : 86400; // one day is default
        
        switch ($backendType) {
            case 'File':
                if ($config->gc_maxlifetime) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " config.inc.php key 'gc_maxlifetime' should be renamed to 'lifetime' and moved to 'session' group.");
                    $maxLifeTime = $config->get('gc_maxlifetime', 86400);
                }
                
                Zend_Session::setOptions(array(
                    'gc_maxlifetime'     => $maxLifeTime
                ));
                
                $sessionSavepath = self::getSessionDir();
                if (ini_set('session.save_path', $sessionSavepath) !== FALSE) {
                    if (!is_dir($sessionSavepath)) {
                        mkdir($sessionSavepath, 0700);
                    }
                }
                
                $lastSessionCleanup = Tinebase_Config::getInstance()->get(Tinebase_Config::LAST_SESSIONS_CLEANUP_RUN);
                if ($lastSessionCleanup instanceof DateTime && $lastSessionCleanup > Tinebase_DateTime::now()->subHour(2)) {
                    Zend_Session::setOptions(array(
                        'gc_probability' => 0,
                        'gc_divisor'     => 100
                    ));
                } else if (@opendir(ini_get('session.save_path')) !== FALSE) {
                    Zend_Session::setOptions(array(
                        'gc_probability' => 1,
                        'gc_divisor'     => 100
                    ));
                } else {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " Unable to initialize automatic session cleanup. Check permissions to " . ini_get('session.save_path'));
                }
                
                break;
                
            case 'Redis':
                
                $host   = ($config->session->host) ? $config->session->host : 'localhost';
                $port   = ($config->session->port) ? $config->session->port : 6379;
                $prefix = Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId() . '_SESSION_';
                
                Zend_Session::setOptions(array(
                    'gc_maxlifetime' => $maxLifeTime,
                    'save_handler'   => 'redis',
                    'save_path'      => "tcp://$host:$port?prefix=$prefix"
                ));
                
                break;
                
            default:
                break;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Session of backend type '{$backendType}' configured.");
    }
    
    public static function setSessionEnabled($sessionName)
    {
        self::setSessionOptions(array(
            'name'   => $sessionName
        ));
        
        self::$_sessionEnabled = true;
    }
    
    /**
     * set session options
     *
     * @param array $_options
     */
    public static function setSessionOptions($options = array())
    {
        $options = array_merge(
            $options,
             array (
                'cookie_httponly' => true,
                'hash_function'   => 1
             )
        );
        
        if (isset($_SERVER['REQUEST_URI'])) {
            $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
            
            if ($request->getHeaders()->has('X-FORWARDED-HOST')) {
                /************** Apache 2.4 with mod_proxy ****************
                 * Apache set's X-FORWARDED-HOST and REFERER
                 * 
                 * ProxyPass /tine20 http://192.168.122.158/tine20
                 * <Location /tine20>
                 *      ProxyPassReverse http://192.168.122.158/tine20
                 * </Location>
                 * 
                 * ProxyPass /192.168.122.158/tine20 http://192.168.122.158/tine20
                 * <Location /192.168.122.158/tine20>
                 *     ProxyPassReverse http://192.168.122.158/tine20
                 * </Location>
                 */
                if ($request->getHeaders()->has('REFERER')) {
                    $refererUri = \Zend\Uri\UriFactory::factory($request->getHeaders()->get('REFERER')->getFieldValue());
                    $baseUri = $refererUri->getPath();
                } else {
                    $exploded = explode("/", $_SERVER['REQUEST_URI']);
                    if (strtolower($exploded[1]) == strtolower($_SERVER['HTTP_HOST'])) {
                         $baseUri = '/' . $_SERVER['HTTP_HOST'] . (($baseUri == '/') ? '' : $baseUri);
                    }
                }
                
            } else {
                $baseUri = $request->getBasePath();
            }
            
            // strip of index.php
            if (substr($baseUri, -9) === 'index.php') {
                $baseUri = dirname($baseUri);
            }
            
            // strip of trailing /
            $baseUri = rtrim($baseUri, '/');
            
            // fix for windows server with backslash directory separator
            $baseUri = str_replace(DIRECTORY_SEPARATOR, '/', $baseUri);
            
            $options['cookie_path'] = $baseUri;
        }
        
        if (!empty($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') {
            $options['cookie_secure'] = true;
        }
        
        Zend_Session::setOptions($options);
    }
    
    public static function getSessionEnabled()
    {
        return self::$_sessionEnabled;
    }
    
    /**
     * Gets Tinebase User session namespace
     *
     * @throws Zend_Session_Exception
     * @return Zend_Session_Namespace
     */
    public static function getSessionNamespace()
    {
        if (! Tinebase_Session::isStarted()) {
            throw new Zend_Session_Exception('Session not started');
        }
        
        if (!self::getSessionEnabled()) {
            throw new Zend_Session_Exception('Session not enabled for request');
        }
        
        try {
           return self::_getSessionNamespace(static::NAMESPACE_NAME);
           
        } catch(Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Session error: ' . $e->getMessage());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }
    }
}