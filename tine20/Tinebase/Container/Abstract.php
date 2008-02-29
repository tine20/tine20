<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class to handle container in each application controller
 * 
 * any record in Tine 2.0 is tied to a container. the rights of an account on a record gets 
 * calculated by the grants given to this account on the container holding the record (if you know what i mean ;-))
 * 
 * @package     Tinebase
 * @subpackage  Container
 */
abstract class Tinebase_Container_Abstract
{
    /**
     * returns the personal container of a given account accessible by the current user
     *
     * @param string $_application the name of the application
     * @param int $_owner the numeric account id of the owner
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getPersonalContainer(Tinebase_Account_Model_Account $_account, $_owner, $_right)
    {
        $application = strtok(get_class($this) , "_");
        
        return Tinebase_Container::getInstance()->getPersonalContainer2($_account, $application, $_owner, $_right);
    }
    
    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param string $_application the name of the application
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getSharedContainer($_account, $_right)
    {
        $applicationName = strtok(get_class($this) , "_");
    }
    
    /**
     * return set of all personal container of other users made accessible to the current account 
     *
     * @param string $_application the name of the application
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getOtherUsersContainer($_account, $_right)
    {
        $applicationName = strtok(get_class($this) , "_");
    }
}