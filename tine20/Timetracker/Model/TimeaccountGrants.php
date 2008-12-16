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
 * @todo        write tests for this
 * @todo        add phpdoc
 * @todo        do we need the constants?
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
     * constant for managa clearing and read complete TA (GRANT_DELETE)
     *
     */
    const MANAGE_CLEARING = 8;

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
        'readGrant'     => 'book_own',
        'addGrant'      => 'view_all',
        'editGrant'     => 'book_all',
        'deleteGrant'   => 'manage_clearing',
        'adminGrant'    => 'manage_all'
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
            'account_id'   => array('presence' => 'required', 'allowEmpty' => TRUE, 'default' => 0),
            'account_type' => array('presence' => 'required', 'InArray' => array('anyone','user','group')),
        
            'book_own'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'view_all'    => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'book_all'   => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'manage_clearing' => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
            ),
            'manage_all'  => array(
                new Zend_Validate_InArray(array(TRUE, FALSE), TRUE), 
                'default' => FALSE
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
            //$timeaccount->container_id = $timeaccount->container_id['id'];
            
            //Tinebase_Core::getLogger()->debug(print_r($_timeaccounts->toArray(), true));
        }
        
    }
    
    /**
     * returns accont_grants of given timeaccount
     *
     * @param  Tinebase_Model_User|int              $_accountId
     * @param  Timetracker_Model_Timeaccount|string $_timeaccountId
     * @param  bool                                 $_ignoreAcl
     * @return array
     */
    public static function getGrantsOfAccount($_accountId, $_timeaccountId, $_ignoreAcl = FALSE)
    {
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
        return $account_grants->toArray();
    }
    
    /**
     * get timeaccounts by grant
     *
     * @param integer $_grant
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public static function getTimeaccountsByAcl($_grant, $_onlyIds = FALSE)
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' get grant: ' . print_r($_grant, true));
        
        $containerIds = Tinebase_Container::getInstance()->getContainerByACL(
            Tinebase_Core::getUser()->getId(),
            'Timetracker',
            $_grant,
            TRUE
        );
        
        // Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' got containers: ' . print_r($containerIds, true));
        
        $filter = new Timetracker_Model_TimeaccountFilter(array(
        ));        
        $filter->container = $containerIds;
        $filter->showClosed = TRUE;
                
        $backend = new Timetracker_Backend_Timeaccount();
        $result = $backend->search($filter, new Tinebase_Model_Pagination());
        
        if ($_onlyIds) {
            return $result->getArrayOfIds();
        } else {
            return $result;
        }
    }
    
    /**
     * returns all grants of a given timeaccount
     *
     * @param  Timetracker_Model_Timeaccount $_timeaccountId
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
        
        return $allTimeaccountGrants;
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
        
        Tinebase_Container::getInstance()->setGrants($_timeaccount->container_id, $grants, TRUE);
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
                'readGrant'     => $grant->book_own,
                'addGrant'      => $grant->view_all,
                'editGrant'     => $grant->book_all,
                'deleteGrant'   => $grant->manage_clearing,
                'adminGrant'    => $grant->manage_all
            )));
        }
        return $result;
    }
}
