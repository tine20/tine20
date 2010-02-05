<?php
/**
 * class to handle grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 *
 * @todo        add memcached support
 * @todo        extend Tinebase_Model_Grants?
 */

/**
 * defines Timeaccount grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 *  */
class Timetracker_Model_TimeaccountGrants extends Tinebase_Record_Abstract
{
    /**
     * constant for book own TS grant (GRANT_READ)
     *
     */
    const BOOK_OWN = 1;

    /**
     * constant for view all TS (GRANT_ADD)
     *
     */
    const VIEW_ALL = 2;

    /**
     * constant for book TS for all users (GRANT_EDIT)
     *
     */
    const BOOK_ALL = 4;

    /**
     * constant for manage billable in all bookable TS (GRANT_DELETE)
     *
     */
    const MANAGE_BILLABLE = 8;

    /**
     * constant for manage all / admin grant (GRANT_ADMIN)
     *
     */
    const MANAGE_ALL = 16;

    /**
     * mapping container_grants => timeaccount_grants
     *
     * @var array
     */
    protected static $_mapping = array(
        Tinebase_Model_Grants::READGRANT     => 'book_own',
        Tinebase_Model_Grants::ADDGRANT      => 'view_all',
        Tinebase_Model_Grants::EDITGRANT     => 'book_all',
        Tinebase_Model_Grants::DELETEGRANT   => 'manage_billable',
        Tinebase_Model_Grants::ADMINGRANT    => 'manage_all'
    );
    
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
    protected $_application = 'Timetracker';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array();
    
    /**
     * overwrite constructor
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     *
     */
    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = NULL)
    {
        $this->_validators = array(
            'id'          => array('Alnum', 'allowEmpty' => TRUE),
            'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => '0'),
            'account_type' => array('presence' => 'required', 'InArray' => array(Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP)),
        
            'book_own'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => TRUE
            ),
            'view_all'    => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => TRUE
            ),
            'book_all'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => TRUE
            ),
            'manage_billable' => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => TRUE
            ),
            'manage_all'  => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE,
                'presence' => 'required',
                'allowEmpty' => TRUE
            )
        );
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * wrapper for Tinebase_Container::hasGrant()
     *
     * @param Timetracker_Model_Timeaccount $_timeaccount
     * @param integer $_grant
     * @return boolean
     */
    public static function hasGrant($_timeaccountId, $_grant)
    {
        $timeaccountBackend = new Timetracker_Backend_Timeaccount();
        $timeaccount = $timeaccountBackend->get($_timeaccountId);
        
        return Tinebase_Container::getInstance()->hasGrant(
            Tinebase_Core::getUser()->getId(), 
            $timeaccount->container_id, 
            $_grant
        );
    }
    
    /**
     * get grants assigned to multiple records
     *
     * @param   Tinebase_Record_RecordSet $_timeaccounts records to get the grants for
     * @param   int|Tinebase_Model_User $_accountId the account to get the grants for
     * @throws  Tinebase_Exception_NotFound
     */
    public static function getGrantsOfRecords(Tinebase_Record_RecordSet $_timeaccounts, $_accountId)
    {
        //$timeaccounts = new Tinebase_Record_RecordSet('Timetracker_Model_Timeaccount', $_records->$_timeaccountProperty);
        Tinebase_Container::getInstance()->getGrantsOfRecords($_timeaccounts, $_accountId);
        
        foreach ($_timeaccounts as $timeaccount) {
            //Tinebase_Core::getLogger()->debug(print_r($timeaccount->toArray(), true));
            
            if (isset($timeaccount->container_id['account_grants']) && is_array($timeaccount->container_id['account_grants'])) {
                $containerGrantsArray = $timeaccount->container_id['account_grants'];
                // mapping
                foreach ($containerGrantsArray as $grantName => $grantValue) {
                    if (array_key_exists($grantName, self::$_mapping)) {
                        $containerGrantsArray[self::$_mapping[$grantName]] = $grantValue;
                    }
                }
                
                $account_grants = new Timetracker_Model_TimeaccountGrants($containerGrantsArray);
                $timeaccount->account_grants = $account_grants->toArray();
                
                $containerId = $timeaccount->container_id;
                $containerId['account_grants'] = $timeaccount->account_grants;
                $timeaccount->container_id = $containerId;
            } 
            //$timeaccount->container_id = $timeaccount->container_id['id'];
            
            //Tinebase_Core::getLogger()->debug(print_r($_timeaccounts->toArray(), true));
        }
        
    }
    
    /**
     * returns account_grants of given timeaccount
     * - this function caches its result (with cache tag 'timeaccountGrants')
     *
     * @param  Tinebase_Model_User|int              $_accountId
     * @param  Timetracker_Model_Timeaccount|string $_timeaccountId
     * @param  bool                                 $_ignoreAcl
     * @return array
     */
    public static function getGrantsOfAccount($_accountId, $_timeaccountId, $_ignoreAcl = FALSE)
    {
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('getGrantsOfAccount' . Tinebase_Model_User::convertUserIdToInt($_accountId) . (($_timeaccountId instanceof Timetracker_Model_Timeaccount) ? $_timeaccountId->getId() : $_timeaccountId) . $_ignoreAcl);
        $result = $cache->load($cacheId);
        
        if (!$result) {
        
            $timeaccount = $_timeaccountId instanceof Timetracker_Model_Timeaccount ? $_timeaccountId : 
                Timetracker_Controller_Timeaccount::getInstance()->get($_timeaccountId);
                
            $containerGrantsArray = Tinebase_Container::getInstance()->getGrantsOfAccount($_accountId, $timeaccount->container_id, $_ignoreAcl)->toArray();
            // mapping
            foreach ($containerGrantsArray as $grantName => $grantValue) {
                if (array_key_exists($grantName, self::$_mapping)) {
                    $containerGrantsArray[self::$_mapping[$grantName]] = $grantValue;
                }
            }
            
            $account_grants = new Timetracker_Model_TimeaccountGrants($containerGrantsArray);
            $result = $account_grants->toArray();
            
            $cache->save($result, $cacheId, array('timeaccountGrants'));
        }
        
        return $result;
    }
    
    /**
     * get timeaccounts by grant
     * - this function caches its result (with cache tag 'timeaccountGrants')
     *
     * @param integer $_grant
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public static function getTimeaccountsByAcl($_grant, $_onlyIds = FALSE)
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' get grant: ' . print_r($_grant, true));
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('getTimeaccountsByAcl' . Tinebase_Core::getUser()->getId() . $_grant . $_onlyIds);
        $result = $cache->load($cacheId);
        
        if (!$result) {
            
            $containerIds = Tinebase_Container::getInstance()->getContainerByACL(
                Tinebase_Core::getUser()->getId(),
                'Timetracker',
                $_grant,
                TRUE
            );
            
            // Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' got containers: ' . print_r($containerIds, true));
            
            $filter = new Tinebase_Model_Filter_FilterGroup(array());
            $filter->addFilter(new Tinebase_Model_Filter_Container('container_id', 'in', $containerIds, array(
                'applicationName' => 'Timetracker',
                'ignoreAcl' => true
            )));
                    
            $backend = new Timetracker_Backend_Timeaccount();
            $result = $backend->search($filter);
            
            if ($_onlyIds) {
                $result = $result->getArrayOfIds();
            }
            
            $cache->save($result, $cacheId, array('timeaccountGrants'));
        }
        
        return $result;
    }
    
    /**
     * returns all grants of a given timeaccount
     * - this function caches its result (with cache tag 'timeaccountGrants')
     *
     * @param  Timetracker_Model_Timeaccount $_timeaccount
     * @param  boolean $_ignoreACL
     * @return Tinebase_Record_RecordSet
     */
    public static function getTimeaccountGrants($_timeaccount, $_ignoreACL = FALSE)
    {
        if (! $_ignoreACL) {
            if (! Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)) {
                if (! self::hasGrant($_timeaccount, self::MANAGE_ALL)) {
                    throw new Tinebase_Exception_AccessDenied("You nor have the RIGHT either the GRANT to get see all grants for this timeaccount");
                }
            }
        }
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = convertCacheId('getTimeaccountGrants' . Tinebase_Core::getUser()->getId() . $_timeaccount->getId() . $_ignoreACL);
        $result = $cache->load($cacheId);
        
        if (!$result) {
                
            $allContainerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_timeaccount->container_id, true);
            $allTimeaccountGrants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants');
            
            foreach ($allContainerGrants as $index => $containerGrants) {
                // mapping
                $containerGrantsArray = $containerGrants->toArray();
                foreach ($containerGrantsArray as $grantName => $grantValue) {
                    if (array_key_exists($grantName, self::$_mapping)) {
                        $containerGrantsArray[self::$_mapping[$grantName]] = $grantValue;
                    }
                }
                $timeaccountGrants = new Timetracker_Model_TimeaccountGrants($containerGrantsArray);
                $allTimeaccountGrants->addRecord($timeaccountGrants);
            }

            $result = $allTimeaccountGrants;
            
            $cache->save($result, $cacheId, array('timeaccountGrants'));
        }
        
        return $result;
    }
    
    /**
     * set timeaccount grants
     *
     * @param Timetracker_Model_Timeaccount $_timeaccount
     * @param Tinebase_Record_RecordSet $_grants
     * @param boolean $_ignoreACL
     */
    public static function setTimeaccountGrants(Timetracker_Model_Timeaccount $_timeaccount, Tinebase_Record_RecordSet $_grants, $_ignoreACL = FALSE)
    {
        if (! $_ignoreACL) {
            if (! Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)) {
                if (! self::hasGrant($_timeaccount, self::MANAGE_ALL)) {
                    throw new Tinebase_Exception_AccessDenied("You nor have the RIGHT either the GRANT to get see all grants for this timeaccount");
                }
            }
        }
        
        // map Timetracker_Model_TimeaccountGrants to Tinebase_Model_Grants
        $grants = self::doMapping($_grants);
        
        Tinebase_Container::getInstance()->setGrants($_timeaccount->container_id, $grants, TRUE, FALSE);
        
        // invalidate cache (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('timeaccountGrants'));
    }
    
    /**
     * map to Tinenbase_Model_Grants
     *
     * @param Tinebase_Record_RecordSet $_grants
     * @return Tinebase_Record_RecordSet
     */
    public static function doMapping(Tinebase_Record_RecordSet $_grants)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        foreach ($_grants as $grant) {
            $result->addRecord(new Tinebase_Model_Grants(array(
                'account_id'    => $grant->account_id,
                'account_type'  => $grant->account_type,
                Tinebase_Model_Grants::READGRANT     => $grant->book_own,
                Tinebase_Model_Grants::ADDGRANT      => $grant->view_all,
                Tinebase_Model_Grants::EDITGRANT     => $grant->book_all,
                Tinebase_Model_Grants::DELETEGRANT   => $grant->manage_billable,
                Tinebase_Model_Grants::ADMINGRANT    => $grant->manage_all
            )));
        }
        return $result;
    }
}
