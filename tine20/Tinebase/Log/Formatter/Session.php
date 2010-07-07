<?php

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
        $userName = $user ? $user->accountDisplayName : '-- none --';
        $output = parent::format($event);
        
        return self::$_sessionId . " $userName - $output";
    }
}