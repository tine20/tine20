<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @deprecated  user backends should be refactored
 * @todo        add searchCount function
 */

/**
 * abstract class for all user backends
 *
 * @package     Tinebase
 * @subpackage  User
 */
 
interface Tinebase_User_Interface
{
    /**
     * get plugins
     * 
     * return array
     */
    public function getPlugins();
    
    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Model_User');
    
    /**
     * get user by property
     *
     * @param   string  $_property
     * @param   string  $_value
     * @param   string  $_accountClass  type of model to return
     * @return  Tinebase_Model_User user
     */
    public function getUserByProperty($_property, $_value, $_accountClass = 'Tinebase_Model_User');
    
    /**
     * register plugins
     * 
     * @param Tinebase_User_Plugin_Interface $_plugin
     */
    public function registerPlugin(Tinebase_User_Plugin_Interface $_plugin);
    
    /**
     * increase bad password counter and store last login failure timestamp if user exists
     * 
     * @param string $_loginName
     */
    public function setLastLoginFailure($_loginName);
}
