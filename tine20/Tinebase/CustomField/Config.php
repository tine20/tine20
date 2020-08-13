<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract backend for custom field configs
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_CustomField_Config extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'customfield_config';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_CustomField_Config';

    /**
     * switch between no system custom fields, only system custom fields and no check at all
     *
     * @var boolean
     */
    protected $_noSystemCFs = true;

    /**
     * switch between no system custom fields, only system custom fields and no check at all
     *
     * @var boolean
     */
    protected $_onlySystemCFs = false;

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';

    public static function getInstance()
    {
        return new self();
    }

    /**
     * will return only system custom fields
     */
    public function setOnlySystemCFs()
    {
        $this->_noSystemCFs = false;
        $this->_onlySystemCFs = true;
    }

    /**
     * will return only non system custom fields
     */
    public function setNoSystemCFs()
    {
        $this->_noSystemCFs = true;
        $this->_onlySystemCFs = false;
    }

    /**
     * will return both non system and system custom fields
     */
    public function setAllCFs()
    {
        $this->_noSystemCFs = false;
        $this->_onlySystemCFs = false;
    }

    /**
     * get the basic select object to fetch records from the database
     *
     * @param array|string $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = self::ALLCOL, $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);

        if ($this->_noSystemCFs) {
            $select->where($this->_db->quoteIdentifier('is_system') . ' = 0');
        }
        if ($this->_onlySystemCFs) {
            $select->where($this->_db->quoteIdentifier('is_system') . ' = 1');
        }

        return $select;
    }

    /**
     * get customfield config ids by grant
     * 
     * @param int $_accountId
     * @param string $_grant if grant is empty, all grants are returned
     * @return array
     */
    public function getByAcl($_grant, $_accountId)
    {
        $select = $this->_getAclSelect('id');
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('customfield_acl.account_grant') . ' = ?', $_grant));
        
        // use grants sql helper fn of Tinebase_Container to add account and grant values
        Tinebase_Container::addGrantsSql($select, $_accountId, $_grant, 'customfield_acl');

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }
        
        return $result;
    }

    /**
     * get acl select
     * 
     * @param string $_cols
     * @return Zend_Db_Select
     */
    protected function _getAclSelect($_cols = '*')
    {
        return $this->_getSelect($_cols)
            ->join(array(
                /* table  */ 'customfield_acl' => SQL_TABLE_PREFIX . 'customfield_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('customfield_acl.customfield_id')} = {$this->_db->quoteIdentifier('customfield_config.id')}",
                /* select */ array()
            );
    }
    
    /**
     * all grants for configs given by array of ids
     * 
     * @param string $_accountId
     * @param array $_ids => account_grants
     * @return array
     */
    public function getAclForIds($_accountId, $_ids)
    {
        $result = array();
        if (empty($_ids)) {
            return $result;
        }
        
        $select = $this->_getAclSelect(array('id' => 'customfield_config.id', 'account_grants' => $this->_dbCommand->getAggregate('customfield_acl.account_grant')));
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('customfield_config.id') . ' IN (?)', (array)$_ids))
               ->group(array('customfield_config.id', 'customfield_acl.account_type', 'customfield_acl.account_id'));
        Tinebase_Container::addGrantsSql($select, $_accountId, Tinebase_Model_CustomField_Grant::getAllGrants(), 'customfield_acl');
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $result[$row['id']] = $row['account_grants'];
        }
        
        return $result;
    }
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Interface $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record)
    {
        $data = $_record->toArray();
        if (is_object($data['definition']) && method_exists($data['definition'], 'toArray')) {
            $data['definition'] = $data['definition']->toArray();
        }
        if (is_array($data['definition'])) {
            $data['definition'] = Zend_Json::encode($data['definition']);
        }
        return $data;
    }

    /**
     * apply modification logs from a replication master locally
     *
     * @param Tinebase_Model_ModificationLog $_modification
     * @throws Tinebase_Exception
     */
    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification)
    {

        switch ($_modification->change_type) {
            case Tinebase_Timemachine_ModificationLog::CREATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                /** @var Tinebase_Model_CustomField_Config $record */
                $model = $_modification->record_type;
                $record = new $model($diff->diff);
                if (Tinebase_Core::getPrimaryTinebaseId() === $record->application_id) {
                    $record->application_id = Tinebase_Core::getTinebaseId();
                }
                Tinebase_CustomField::getInstance()->addCustomField($record);
                break;

            case Tinebase_Timemachine_ModificationLog::UPDATED:
                $diff = new Tinebase_Record_Diff(json_decode($_modification->new_value, true));
                $record = $this->get($_modification->record_id, true);
                $record->applyDiff($diff);
                if (Tinebase_Core::getPrimaryTinebaseId() === $record->application_id) {
                    $record->application_id = Tinebase_Core::getTinebaseId();
                }
                Admin_Controller_Customfield::getInstance()->update($record);
                break;

            case Tinebase_Timemachine_ModificationLog::DELETED:
                Tinebase_CustomField::getInstance()->deleteCustomField($_modification->record_id);
                break;

            default:
                throw new Tinebase_Exception('unknown Tinebase_Model_ModificationLog->change_type: ' . $_modification->change_type);
        }
    }
}
