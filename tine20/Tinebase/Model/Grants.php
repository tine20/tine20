<?php
/**
 * grants model of a container
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * grants model
 * 
 * @package     Tinebase
 * @subpackage  Record
 *  
 */
class Tinebase_Model_Grants extends Tinebase_Record_Abstract
{
    /**
     * grant to read all records of a container / a single record
     */
    const GRANT_READ     = 'readGrant';
    
    /**
     * grant to add a record to a container
     */
    const GRANT_ADD      = 'addGrant';
    
    /**
     * grant to edit all records of a container / a single record
     */
    const GRANT_EDIT     = 'editGrant';
    
    /**
     * grant to delete  all records of a container / a single record
     */
    const GRANT_DELETE   = 'deleteGrant';
    
    /**
     * grant to _access_ records marked as private (GRANT_X = GRANT_X * GRANT_PRIVATE)
     */
    const GRANT_PRIVATE = 'privateGrant';
    
    /**
     * grant to export all records of a container / a single record
     */
    const GRANT_EXPORT = 'exportGrant';
    
    /**
     * grant to sync all records of a container / a single record
     */
    const GRANT_SYNC = 'syncGrant';
    
    /**
     * grant to administrate a container
     */
    const GRANT_ADMIN    = 'adminGrant';
    
    /**
     * grant to see freebusy info in calendar app
     * @todo move to Calendar_Model_Grant once we are able to cope with app specific grant classes
     */
    const GRANT_FREEBUSY = 'freebusyGrant';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

    /**
     * constructor
     * 
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     */
    public function __construct($_data = null, $_bypassFilters = false, $_convertDates = null)
    {
        $this->_validators = array(
            'id'            => array('Alnum', 'allowEmpty' => true),
            'record_id'     => array('allowEmpty' => true),
            'account_grant' => array('allowEmpty' => true),
            'account_id'    => array('presence' => 'required', 'allowEmpty' => true, 'default' => '0'),
            'account_type'  => array('presence' => 'required', array('InArray', array(
                Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            ))),
        );
        
        foreach ($this->getAllGrants() as $grant) {
            $this->_validators[$grant] = array(
                new Zend_Validate_InArray(array(true, false), true), 
                'default' => false,
                'presence' => 'required',
                'allowEmpty' => true
            );
            
            // initialize in case validators are switched off
            $this->_properties[$grant] = false;
            
        }
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_ADD,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
            self::GRANT_PRIVATE,
            self::GRANT_EXPORT,
            self::GRANT_SYNC,
            self::GRANT_ADMIN,
            self::GRANT_FREEBUSY,
        );
    
        return $allGrants;
    }

    /**
     * checks record grant
     * 
     * @param string $grant
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     */
    public function userHasGrant($grant, $user = null)
    {
        if ($user === null) {
            $user = Tinebase_Core::getUser();
        }
        
        // @todo switch to TRACE
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Grant ' . print_r($this->toArray(), true));
        
        if (! is_object($user)) {
            // @todo switch to TRACE
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No user object');
            return false;
        }
        
        if (! in_array($grant, $this->getAllGrants()) || ! isset($this->{$grant}) || ! $this->{$grant}) {
            // @todo switch to TRACE
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Grant not defined or not set');
            return false;
        }
        
        switch ($this->account_type) {
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                if (! in_array($user->getId(), Tinebase_Group::getInstance()->getGroupMembers($this->account_id))) {
                    // @todo switch to TRACE
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Current user not member of group ' . $this->account_id);
                    return false;
                }
                break;
            case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                if ($user->getId() !== $this->account_id) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Grant not available for current user (account_id of grant: ' . $this->account_id . ')');
                    return false;
                }
        }
        
        return true;
    }
}
