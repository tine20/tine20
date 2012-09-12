<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Log Formatter for Tine 2.0
 * - prefixes log statements
 * - replaces passwords
 * - adds user name
 * 
 * @package     Tinebase
 * @subpackage  Log
 */
class Tinebase_Log_Formatter extends Zend_Log_Formatter_Simple
{
    /**
     * session id
     * 
     * @var string
     */
    protected static $_prefix;
    
    /**
     * username
     * 
     * @var string
     */
    protected static $_username = NULL;
    
    /**
     * search strings
     * 
     * @var array
     */
    protected $_search = array();
    
    /**
     * replacement strings
     * 
     * @var array
     */
    protected $_replace = array();
    
    /**
     * Add session id in front of log line
     *
     * @param  array    $event    event data
     * @return string             formatted line to write to the log
     */
    public function format($event)
    {
        if (! self::$_prefix) {
            self::$_prefix = Tinebase_Record_Abstract::generateUID(5);
        }
        
        $output = parent::format($event);
        $output = str_replace($this->_search, $this->_replace, $output);
        
        return self::getPrefix() . ' ' . self::getUsername() . ' - ' . $output;
    }
    
    /**
     * set formatter replacements
     * - Replace LDAP and SQL passwords with ********
     */
    public function setReplacements()
    {
        $config = Tinebase_Core::getConfig();
        if (isset($config->database) && !empty($config->database->password)) {
            $this->_search[] = $config->database->password;
            $this->_replace[] = '********';
        }
        if (isset($config->{Tinebase_Config::AUTHENTICATIONBACKEND}) && !empty($config->{Tinebase_Config::AUTHENTICATIONBACKEND}->password)) {
            $this->_search[] = $config->{Tinebase_Config::AUTHENTICATIONBACKEND}->password;
            $this->_replace[] = '********';
        }
    }
    
    /**
     * get current prefix
     * 
     * @return string
     */
    public static function getPrefix()
    {
        return self::$_prefix;
    }
    
    /**
     * get current username
     * 
     * @return string
     */
    public static function getUsername()
    {
        if (self::$_username === NULL) {
            $user = Tinebase_Core::getUser();
            self::$_username = ($user && is_object($user))
                ? (isset($user->accountLoginName)
                    ? $user->accountLoginName
                    : (isset($user->accountDisplayName) ? $user->accountDisplayName : NULL)) 
                : NULL;
        }
        
        return (self::$_username) ? self::$_username : '-- none --';
    }
    
    /**
     * set/append prefix
     * 
     * @param string $prefix
     * @param bool $append
     */
    public static function setPrefix($prefix, $append = TRUE)
    {
        if ($append) {
            $prefix = self::getPrefix() . " $prefix";
        }
        
        self::$_prefix = $prefix;
    }
}
