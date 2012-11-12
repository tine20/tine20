<?php
/**
 * class to handle grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines Timeaccount grants
 * 
 * @package     Timetracker
 * @subpackage  Record
 *  */
class Timetracker_Model_TimeaccountGrants extends Tinebase_Model_Grants
{
    /**
     * constant for book own TS grant
     *
     */
    const BOOK_OWN = 'bookOwnGrant';

    /**
     * constant for view all TS 
     *
     */
    const VIEW_ALL = 'viewAllGrant';

    /**
     * constant for book TS for all users
     *
     */
    const BOOK_ALL = 'bookAllGrant';

    /**
     * constant for manage billable in all bookable TS
     *
     */
    const MANAGE_BILLABLE = 'manageBillableGrant';

    /**
     * key in $_validators/$_properties array for the field which 
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
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::BOOK_OWN,
            self::VIEW_ALL,
            self::BOOK_ALL,
            self::MANAGE_BILLABLE,
            Tinebase_Model_Grants::GRANT_EXPORT,
            Tinebase_Model_Grants::GRANT_ADMIN,
        );
    
        return $allGrants;
    }
    
    /**
     * wrapper for Tinebase_Container::hasGrant()
     *
     * @param Timetracker_Model_Timeaccount     $_timeaccount
     * @param array|string                      $_grant
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
        Tinebase_Container::getInstance()->getGrantsOfRecords($_timeaccounts, $_accountId, 'container_id', 'Timetracker_Model_TimeaccountGrants');
        
        foreach ($_timeaccounts as $timeaccount) {
            if (isset($timeaccount->container_id['account_grants']) && is_array($timeaccount->container_id['account_grants'])) {
                $containerGrantsArray = $timeaccount->container_id['account_grants'];
                
                $account_grants = new Timetracker_Model_TimeaccountGrants($containerGrantsArray);
                $timeaccount->account_grants = $account_grants->toArray();
                
                $containerId = $timeaccount->container_id;
                $containerId['account_grants'] = $timeaccount->account_grants;
                $timeaccount->container_id = $containerId;
            } 
        }
    }
    
    /**
     * returns account_grants of given timeaccount
     * - this function caches its result (with cache tag 'container')
     *
     * @param  Tinebase_Model_User|int              $_accountId
     * @param  Timetracker_Model_Timeaccount|string $_timeaccountId
     * @param  bool                                 $_ignoreAcl
     * @return array
     */
    public static function getGrantsOfAccount($_accountId, $_timeaccountId, $_ignoreAcl = FALSE)
    {
        $timeaccount = ($_timeaccountId instanceof Timetracker_Model_Timeaccount) ? $_timeaccountId : Timetracker_Controller_Timeaccount::getInstance()->get($_timeaccountId);
        $container = Tinebase_Container::getInstance()->getContainerById($timeaccount->container_id);
        $cache = Tinebase_Core::getCache();
        $cacheId = convertCacheId('getGrantsOfAccount' . Tinebase_Model_User::convertUserIdToInt($_accountId) . $timeaccount->getId() . $_ignoreAcl . $container->last_modified_time);
        $result = $cache->load($cacheId);
        
        if ($result === FALSE) {
        
            $containerGrantsArray = Tinebase_Container::getInstance()->getGrantsOfAccount($_accountId, $timeaccount->container_id, 'Timetracker_Model_TimeaccountGrants')->toArray();
            
            $account_grants = new Timetracker_Model_TimeaccountGrants($containerGrantsArray);
            $result = $account_grants->toArray();
            
            $cache->save($result, $cacheId, array('container'));
        }
        
        return $result;
    }
    
    /**
     * get timeaccounts by grant
     *
     * @param array|string $_grant
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public static function getTimeaccountsByAcl($_grant, $_onlyIds = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' get grant: ' . print_r($_grant, true));
        
        $containerIds = Tinebase_Container::getInstance()->getContainerByACL(
            Tinebase_Core::getUser()->getId(),
            'Timetracker',
            $_grant,
            TRUE
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' got containers: ' . print_r($containerIds, true));
        
        $filter = new Tinebase_Model_Filter_FilterGroup(array());
        // NOTE: use id filter instead of container filter because of poor performance of container filter (setValue)
        $filter->addFilter(new Tinebase_Model_Filter_Id('container_id', 'in', $containerIds));

        $backend = new Timetracker_Backend_Timeaccount();
        $result = $backend->search($filter);
        
        if ($_onlyIds) {
            $result = $result->getArrayOfIds();
        }
        
        return $result;
    }
    
    /**
     * returns all grants of a given timeaccount
     * - this function caches its result (with cache tag 'container')
     *
     * @param  Timetracker_Model_Timeaccount $_timeaccount
     * @param  boolean $_ignoreACL
     * @return Tinebase_Record_RecordSet
     */
    public static function getTimeaccountGrants($_timeaccount, $_ignoreACL = FALSE)
    {
        if (! $_ignoreACL) {
            if (! Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)) {
                if (! self::hasGrant($_timeaccount, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied("You nor have the RIGHT either the GRANT to get see all grants for this timeaccount");
                }
            }
        }
        
        $container = Tinebase_Container::getInstance()->getContainerById($_timeaccount->container_id);
        $cache = Tinebase_Core::getCache();
        $cacheId = convertCacheId('getTimeaccountGrants' . Tinebase_Core::getUser()->getId() . $_timeaccount->getId() . $_ignoreACL . $container->last_modified_time);
        $result = $cache->load($cacheId);
        
        if ($result === FALSE) {
            
            $allContainerGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($_timeaccount->container_id, true, 'Timetracker_Model_TimeaccountGrants');
            $allTimeaccountGrants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants');
            
            foreach ($allContainerGrants as $index => $containerGrants) {
                $timeaccountGrants = new Timetracker_Model_TimeaccountGrants($containerGrants->toArray());
                $allTimeaccountGrants->addRecord($timeaccountGrants);
            }

            $result = $allTimeaccountGrants;
            
            $cache->save($result, $cacheId, array('container'));
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
                if (! self::hasGrant($_timeaccount, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    throw new Tinebase_Exception_AccessDenied("You nor have the RIGHT either the GRANT to get see all grants for this timeaccount");
                }
            }
        }
        
        Tinebase_Container::getInstance()->setGrants($_timeaccount->container_id, $_grants, TRUE, FALSE);
    }
}
