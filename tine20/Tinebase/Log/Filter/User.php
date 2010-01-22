<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        make this configurable
 */

/**
 * user filter for Zend_Log logger
 * 
 * @package     Tinebase
 * @subpackage  Log
 */
class Tinebase_Log_Filter_User implements Zend_Log_Filter_Interface
{
    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param  array    $event    event data
     * @return boolean            accepted?
     */
    public function accept($event)
    {
         $logUsers = array(
            // @todo add users here
         );
    
         $username = Tinebase_Core::getUser()->accountLoginName;
         return in_array($username, $logUsers);    
    }  
}
