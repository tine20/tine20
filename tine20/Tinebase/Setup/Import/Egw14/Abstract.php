<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 */

/**
 * abstract class to import data from egw14
 * 
 * 
 * @package     Tinebase
 * @subpackage  Setup
 */
abstract class Tinebase_Setup_Import_Egw14_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_egwDb = NULL;
    
    /**
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * @var Zend_Log
     */
    protected $_log = NULL;
    
    /**
     * 
     * @var array
     */
    protected $_accountIdMapCache = array();
    
    /**
     * map egwPriority => tine prioroty
     * 
     * @see etemplate/inc/class.select_widget.inc.php
     * @var array
     */
    protected $_priorityMap = array(
        0 => NULL,  // not set
        1 => 0,     // low
        2 => 1,     // normaml
        3 => 2,     // high
    );
    
    /**
     * map egwGrant => tine grant
     * 
     * @todo   move to a generic egw import helper
     * @see phpgwapi/inc/class.egw.inc.php
     * @var array
     */
    protected $_grantMap = array(
        1 => Tinebase_Model_Grants::GRANT_READ,
        2 => Tinebase_Model_Grants::GRANT_ADD,
        4 => Tinebase_Model_Grants::GRANT_EDIT,
        8 => Tinebase_Model_Grants::GRANT_DELETE,
    );
    
    /**
     * constructs this importer
     * 
     * @param Zend_Db_Adapter_Abstract  $_egwDb
     * @param Zend_Config               $_config
     * @param Zend_Log                  $_log
     */
    public function __construct($_egwDb, $_config, $_log)
    {
        $this->_egwDb  = $_egwDb;
        $this->_config = $_config;
        $this->_log    = $_log;
    }
    
    /**
     * do the import
     */
    abstract public function import();
    
    /**
     * converts egw date to Zend_Date
     * 
     * @param  int $_egwTS
     * @param  string $_tz timezone
     * @return Zend_Date
     */
    public function convertDate($_egwTS, $_tz)
    {
        if (! $_egwTS) {
            return NULL;
        }
        
        date_default_timezone_set($_tz);
        $date = new Zend_Date($_egwTS, Zend_Date::TIMESTAMP);
        date_default_timezone_set('UTC');
        
        return $date;
    }
    
    /**
     * converts egw -> tine priority
     * 
     * @param  int $_egwPrio
     * @return mixed
     */
    public function getPriority($_egwPrio)
    {
        return $this->_priorityMap[(int) $_egwPrio];
    }
    
    /**
     * maps egw account to tine 2.0 user/group Id
     * 
     * @param  int $_egwAccountId
     * @return string 
     */
    public function mapAccountIdEgw2Tine($_egwAccountId)
    {
        if (! isset ($this->_accountIdMapCache[$_egwAccountId])) {
            
            $tineUserId = $_egwAccountId;
            if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
                if ((int) $_egwAccountId < 0) {
                    $this->_accountIdMapCache[$_egwAccountId] = Tinebase_Group::getInstance()->resolveGIdNumberToUUId($_egwAccountId);
                } else {
                    $this->_accountIdMapCache[$_egwAccountId] = Tinebase_User::getInstance()->resolveUIdNumberToUUId($_egwAccountId);
                }
            } else {
                $this->_accountIdMapCache[$_egwAccountId] = abs($_egwAccountId);
            }
        }
        
        return $this->_accountIdMapCache[$_egwAccountId];
    }
    
    /**
     * maps tine 2.0 user/group Id to egw account Id
     * 
     * @param  int      $_tineAccountId
     * @param  string   $_accountType
     * @return string 
     */
    public function mapAccountIdTine2Egw($_tineAccountId, $_accountType = 'User')
    {
        $egwAccountId = NULL;
        
        $keys = array_keys($this->_accountIdMapCache, $_tineAccountId);
        foreach ((array) $keys as $candidate) {
            if ($_accountType == 'User' && (int) $candidate > 0) {
                $egwAccountId = $candidate;
            } else if ($_accountType == 'Group' && (int) $candidate < 0) {
                $egwAccountId = $candidate;
            }
        }
        
        // not in cache
        if (! $egwAccountId) {
            if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
                if ($_accountType == 'User') {
                    $this->_accountIdMapCache[$egwAccountId] = Tinebase_User::getInstance()->resolveUUIdToUIdNumber($_tineAccountId);
                } else {
                    $this->_accountIdMapCache[$egwAccountId] = Tinebase_Group::getInstance()->resolveUUIdToGIdNumber($_tineAccountId);
                }
            } else {
                $egwAccountId = ($_accountType == 'User' ? '' : '-') . $_tineAccountId;
            }
            
            $this->_accountIdMapCache[$egwAccountId] = $_tineAccountId;
        }
        
        return $egwAccountId;
    }
    
    /**
     * returns grants by owner
     * 
     * eGW has owner based grants whereas Tine 2.0 has container based grants.
     * this class reads the egw owner grants and converts them into Tine 2.0 grants
     * attacheable to a tine 2.0 container
     * 
     * @param  string $_application
     * @param  string $_accountId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grant
     * @throws Tinebase_Exception_NotFound
     */
    public function getGrantsByOwner($_application, $_accountId)
    {
        $egwAccountId = $this->mapAccountIdTine2Egw($_accountId);
        $acl_account = array($egwAccountId);
        
        if ($egwAccountId > 0) {
            $user     = Tinebase_User::getInstance()->getUserById($_accountId);
            $groupIds = $user->getGroupMemberships();
            
            
            foreach($groupIds as $groupId) {
                $acl_account[] = '-' . $this->mapAccountIdTine2Egw($groupId, 'Group');
            }
        }
        
        $select = $this->_egwDb->select()
            ->from(array('grants' => 'egw_acl'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('acl_appname') . ' = ?', $_application))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('acl_account') . ' IN (?)', $acl_account));
            
        $egwGrantDatas = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        //print_r($egwGrantDatas);
        
        // in a first run we merge grants from different sources
        $effectiveGrants = array();
        if ($egwAccountId > 0) {
            // owner has implicitly all grants in egw
            $effectiveGrants[$egwAccountId] = 15;
        }
        foreach ($egwGrantDatas as $egwGrantData) {
            // grants are int != 0
            if ( (int) $egwGrantData['acl_location'] == 0) {
                continue;
            }
            
            // NOTE: The grant source is not resolveable in Tine 2.0!
            //       In Tine 2.0 grants are directly given to a container
            $grantsSource      = $egwGrantData['acl_account'];
            $grantsDestination = $egwGrantData['acl_location'];
            $grantsGiven       = $egwGrantData['acl_rights'];
            
            if (! array_key_exists($grantsDestination, $effectiveGrants)) {
                $effectiveGrants[$grantsDestination] = 0;
            }
            $effectiveGrants[$grantsDestination] |= $grantsGiven;
        }
        //print_r($effectiveGrants);
        
        // convert to tine grants
        $tineGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        foreach ($effectiveGrants as $grantAccount => $egwGrants) {
            $tineGrant = new Tinebase_Model_Grants(array(
                'account_id' => $this->mapAccountIdEgw2Tine($grantAccount),
                'account_type' => (int) $grantAccount > 0 ? 
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_USER : 
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            ));
            
            foreach ($this->_grantMap as $egwGrant => $tineGrantString) {
                $tineGrant->{$tineGrantString} = (bool) ($egwGrants & $egwGrant);
            }
            
            // the owner also gets admin grants
            if ($egwAccountId > 0 && $grantAccount == $egwAccountId) {
                $tineGrant->{Tinebase_Model_Grants::GRANT_ADMIN} = TRUE;
            }
            
            $tineGrants->addRecord($tineGrant);
        }
        //print_r($tineGrants->toArray());
        
        // for group owners (e.g. group addressbooks) we need an container admin
        if ($egwAccountId < 0) {
            $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
            $tineGrant = new Tinebase_Model_Grants(array(
                'account_id' => $this->mapAccountIdEgw2Tine($_accountId),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            ));
            $tineGrant->{Tinebase_Model_Grants::GRANT_ADMIN} = TRUE;
            
            $tineGrants->addRecord($tineGrant);
        }
        
        return $tineGrants;
    }
}