<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * prefixes log statements with session info
 */
class Tinebase_Log_Formatter_Session extends Zend_Log_Formatter_Simple
{
    static $_sessionId;
    
    /**
     * Add session id in front of log line
     * Replace LDAP and SQL passwords with ********
     *
     * @param  array    $event    event data
     * @return string             formatted line to write to the log
     */
    public function format($event)
    {
        if (! self::$_sessionId) {
            self::$_sessionId = substr(Tinebase_Record_Abstract::generateUID(), 0, 5);
        }
        
        $user = Tinebase_Core::getUser();
        $userName = ($user && is_object($user)) ? $user->accountDisplayName : '-- none --';
        $output = parent::format($event);
        
        // replace passwords
        $config = Tinebase_Core::getConfig();
        
        $search = array();
        $replace = array();
        
        if (isset($config->database) && !empty($config->database->password)) {
            $search[] = '/' . $config->database->password . '/';
            $replace[] = '********';
        }
        if (isset($config->{Tinebase_Config::AUTHENTICATIONBACKEND}) && !empty($config->{Tinebase_Config::AUTHENTICATIONBACKEND}->password)) {
            $search[] = '/' . $config->{Tinebase_Config::AUTHENTICATIONBACKEND}->password . '/';
            $replace[] = '********';
        }
        
        $output = preg_replace($search, $replace, $output);
        
        return self::$_sessionId . " $userName - $output";
    }
}
