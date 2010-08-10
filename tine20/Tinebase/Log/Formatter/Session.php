<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * prefixes log statements with session info
 */
class Tinebase_Log_Formatter_Session extends Zend_Log_Formatter_Simple
{
    static $_sessionId;
    
    /**
     * Formats data into a single line to be written by the writer.
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
        
        return self::$_sessionId . " $userName - $output";
    }
}
