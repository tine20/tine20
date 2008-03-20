<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Container
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
    abstract public function createPersonalFolder(Tinebase_Account_Model_Account $_account);
    
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
        
        $container = Tinebase_Container::getInstance()->getPersonalContainer($_account, $application, $_owner, $_right);
        
        // if we want to read the personal folders of the current user
        // and don't get back any container
        if(count($container) === 0 && $_account->accountId == $_owner) {
            $container = $this->createPersonalFolder($_account);
        }
        
        return $container;
    }

    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param string $_application the name of the application
     * @return Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getSharedContainer(Tinebase_Account_Model_Account $_account, $_right)
    {
        $application = strtok(get_class($this) , "_");
        
        $container = Tinebase_Container::getInstance()->getSharedContainer($_account, $application, $_right);
        
        return $container;
    }
    
    /**
     * return set of all personal container of other users made accessible to the current account 
     *
     * @param string $_application the name of the application
     * @return Tinebase_Record_RecordSet set of Tinebase_Account_Model_Account
     */
    public function getOtherUsers(Tinebase_Account_Model_Account $_account, $_right)
    {
        $application = strtok(get_class($this) , "_");
        
        $container = Tinebase_Container::getInstance()->getOtherUsers($_account, $application, $_right);
        
        return $container;
    }
}