<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_Preference extends Tinebase_Backend_Sql_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * timezone pref const
     *
     */
    const TIMEZONE = 'timezone';

    /**
     * locale pref const
     *
     */
    const LOCALE = 'locale';
    
    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Tinebase';    
    
    /**************************** backend settings *********************************/
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'preferences';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Preference';
    
    /**************************** public functions *********************************/
    
    /**
     * get value of preference
     *
     * @param string $_preferenceName
     * @param string $_default return this if no preference found and default given
     * @return string
     * @throws Tinebase_Exception_NotFound if no default given and no pref found
     */
    public function getValue($_preferenceName, $_default = NULL)
    {
        $accountId = (Tinebase_Core::isRegistered(Tinebase_Core::USER)) ? Tinebase_Core::getUser()->getId() : 0; 
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' get ' . $_preferenceName . ' for user ' . $accountId);
        
        try {
            $result = $this->getValueForUser(
                $_preferenceName, $accountId, 
                ($accountId === 0) 
                    ? Tinebase_Model_Preference::ACCOUNT_TYPE_ANYONE
                    : Tinebase_Model_Preference::ACCOUNT_TYPE_USER
            ); 
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($_default !== NULL) {
                $result = $_default;
            } else {
                throw $tenf;
            }
        }
        
        return $result; 
    }
    
    /**
     * get value of preference for a user/group
     *
     * @param string $_preferenceName
     * @param integer $_userId
     * @param string $_accountType
     * @return string
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo add param for getting default value ?
     */
    public function getValueForUser($_preferenceName, $_accountId, $_accountType = 'user')
    {

        $select = $this->_getSelect('*');
        
        // build query: ... WHERE (user OR group OR anyone) AND name AND application_id
        
        // user
        $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($select);
        
        $groupSelectUser = new Tinebase_Backend_Sql_Filter_GroupSelect($groupSelect);
        $groupSelectUser
            ->where($this->_db->quoteIdentifier($this->_tableName . '.account_id') . ' = ?', $_accountId)
            ->where($this->_db->quoteIdentifier($this->_tableName . '.account_type') . ' = ?', $_accountType);
        $groupSelectUser->appendWhere(Zend_Db_Select::SQL_OR);
        
        if ($_accountId !== 0) {
            // anyone / default
            $groupSelectAnyone = new Tinebase_Backend_Sql_Filter_GroupSelect($groupSelect);
            $groupSelectAnyone
                ->where($this->_db->quoteIdentifier($this->_tableName . '.account_id') . ' = 0')
                ->where($this->_db->quoteIdentifier($this->_tableName . '.account_type') . ' = ?', Tinebase_Model_Preference::ACCOUNT_TYPE_ANYONE);
            $groupSelectAnyone->appendWhere(Zend_Db_Select::SQL_OR);
                
            // group
            $groups = Tinebase_Group::getInstance()->getGroupMemberships($_accountId);
            $groupSelectGroups = new Tinebase_Backend_Sql_Filter_GroupSelect($groupSelect);
            $groupSelectGroups
                ->where($this->_db->quoteIdentifier($this->_tableName . '.account_id') . ' IN (?)', $groups)
                ->where($this->_db->quoteIdentifier($this->_tableName . '.account_type') . ' = ?', Tinebase_Model_Preference::ACCOUNT_TYPE_GROUP);
            $groupSelectGroups->appendWhere(Zend_Db_Select::SQL_OR);
        }
        $groupSelect->appendWhere(Zend_Db_Select::SQL_AND);
        
        // name + app id
        $select
            ->where($this->_db->quoteIdentifier($this->_tableName . '.name') . ' = ?', $_preferenceName)
            ->where(
                $this->_db->quoteIdentifier($this->_tableName . '.application_id') . ' = ?', 
                Tinebase_Application::getInstance()->getApplicationByName($this->_application)->getId()
            );
        
            
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());

        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetchAll();
        
        if (!$queryResult) {
            throw new Tinebase_Exception_NotFound("No matching preference for '$_preferenceName' found!");
        }

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        // get the correct result 
        $pref = $this->_getMatchingPreference($this->_rawDataToRecordSet($queryResult));
                
        $result = $pref->value;

        return $result;
    }

    /**
     * set value of preference
     *
     * @param string $_preferenceName
     * @param string $_value
     */
    public function setValue($_preferenceName, $_value)
    {
        $accountId = (Tinebase_Core::isRegistered(Tinebase_Core::USER)) ? Tinebase_Core::getUser()->getId() : 0; 

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' set ' . $_preferenceName . ' for user ' . $accountId . ':' . $_value);
        
        return $this->setValueForUser($_preferenceName, $_value, $accountId);
    }
    
    /**
     * get value of preference for a user/group
     *
     * @param string $_preferenceName
     * @param integer $_userId
     * @return string
     */
    public function setValueForUser($_preferenceName, $_value, $_accountId) 
    {
        $appId = Tinebase_Application::getInstance()->getApplicationByName($this->_application)->getId();
        
        // check if already there -> update
        $select = $this->_getSelect('*');
        $select
            ->where($this->_db->quoteIdentifier($this->_tableName . '.account_id') . ' = ?', $_accountId)
            ->where($this->_db->quoteIdentifier($this->_tableName . '.account_type') . ' = ?', Tinebase_Model_Preference::ACCOUNT_TYPE_USER)
            ->where($this->_db->quoteIdentifier($this->_tableName . '.name') . ' = ?', $_preferenceName)
            ->where($this->_db->quoteIdentifier($this->_tableName . '.application_id') . ' = ?', $appId);
            
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
                
        if (!$queryResult) {
            // no preference yet -> create
            $preference = new Tinebase_Model_Preference(array(
                'application_id'    => $appId,
                'name'              => $_preferenceName,
                'value'             => $_value,
                'account_id'        => $_accountId,
                'account_type'      => Tinebase_Model_Preference::ACCOUNT_TYPE_USER,
                'type'              => Tinebase_Model_Preference::TYPE_NORMAL
            ));
            $this->create($preference);
            
        } else {
            $preference = $this->_rawDataToRecord($queryResult);
            $preference->value = $_value;
            $this->update($preference);
        }
    }
    
    /**************************** protected functions *********************************/
    
    /**
     * get matching preference from result set
     * order: forced > user > group > default
     * 
     * @param Tinebase_Record_RecordSet $_preferences
     * @return Tinebase_Model_Preference
     * 
     * @todo add more sorting here?
     */
    protected function _getMatchingPreference(Tinebase_Record_RecordSet $_preferences)
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_preferences->toArray(), TRUE));
        
        if (count($_preferences) == 1) {
            $result = $_preferences->getFirstRecord();
        } else {
            $_preferences->addIndices(array('type', 'account_type'));
            
            // check forced
            $forced = $_preferences->filter('type', Tinebase_Model_Preference::TYPE_FORCED);
            if (count($forced) > 0) {
                $_preferences = $forced;
            } 
            
            // check user
            $user = $_preferences->filter('account_type', Tinebase_Model_Preference::ACCOUNT_TYPE_USER);
            if (count($user) > 0) {
                $result = $user->getFirstRecord();
            } else {
                // check group
                $group = $_preferences->filter('account_type', Tinebase_Model_Preference::ACCOUNT_TYPE_GROUP);
                if (count($group) > 0) {
                    $result = $group->getFirstRecord();
                } else {                
                    // get first record of the remaining result set (defaults/anyone)
                    $result = $_preferences->getFirstRecord();
                }
            }
        }

        return $result;
    }
}
