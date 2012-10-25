<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Sipgate application
 *
 * @package     Sipgate
 */
class Sipgate_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_resolveUserFields = array(
        'Sipgate_Model_Account' => array('created_by', 'last_modified_by', 'deleted_by'),
        'Sipgate_Model_Line'    => array('user_id')
    );
    /**
     * the line controller
     * @var Sipgate_Controller_Line
     */
    protected $_lineController = NULL;

    /**
     * the account controller
     * @var Sipgate_Controller_Account
     */
    protected $_accountController = NULL;

    /**
     * the connection controller
     * @var Sipgate_Controller_Connection
     */
    protected $_connectionController = NULL;

    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Sipgate';

    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getAccount($id)
    {
        return $this->_get($id, Sipgate_Controller_Account::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveAccount($recordData)
    {
        return $this->_save($recordData, Sipgate_Controller_Account::getInstance(), 'Account');
    }

    /**
     * validates a account
     *
     * @param  array $account
     * @return array created/updated record
     */
    public function validateAccount($account)
    {
        return array('result' => array('success' => Sipgate_Controller_Account::getInstance()->validateAccount($account)));
    }

    /**
     * sends a message
     * @param unknown_type $values
     * @param unknown_type $lineId
     */
    public function sendMessage($values, $lineId) {
        return Sipgate_Controller_Line::getInstance()->sendMessage($values, $lineId);
    }

    /**
     * synchronizes the lines of an account
     * @param string $accountId
     */
    public function syncLines($accountId) {
        return $this->_recordToJson(Sipgate_Controller_Line::getInstance()->syncAccount($accountId));
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteAccounts($ids)
    {
        return $this->_delete($ids, Sipgate_Controller_Account::getInstance());
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAccounts($filter, $paging)
    {
        return $this->_search($filter, $paging, Sipgate_Controller_Account::getInstance(), 'Sipgate_Model_AccountFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getConnection($id)
    {
        return $this->_get($id, Sipgate_Controller_Connection::getInstance());
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchConnections($filter, $paging)
    {
        return $this->_search($filter, $paging, Sipgate_Controller_Connection::getInstance(), 'Sipgate_Model_ConnectionFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getLine($id)
    {
        return $this->_get($id, Sipgate_Controller_Line::getInstance());
    }

    public function saveLine($recordData)
    {
        return $this->_save($recordData, Sipgate_Controller_Line::getInstance(), 'Line');
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchLines($filter, $paging)
    {
        return $this->_search($filter, $paging, Sipgate_Controller_Line::getInstance(), 'Sipgate_Model_LineFilter', false);
    }

    /**
     * synchronizes the connections and the associated contacts for one or more lines
     * @param array $lines
     */
    public function syncConnections($lines)
    {
        if(is_array($lines) && count($lines)>0) {
            foreach($lines as $line) {
                $ids[] = $line['id'];
            }
        }

        $results = array();
        $paging = new Tinebase_Model_Pagination();
        $filter = new Sipgate_Model_LineFilter(array('AND', array()));
        $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'in', $ids));
        $lines = Sipgate_Controller_Line::getInstance()->search($filter);

        $totalcount = 0;
        if($lines->count()) {
            $scc = Sipgate_Controller_Connection::getInstance();
            
            $scc->syncContacts($lines);
            
            foreach($lines as $line) {
                $count = $scc->syncLine($line);
                $totalcount += $count;
                $results[$line->getId()] = $count;
            }
        }
        return array('success' => true, 'results' => $results, 'totalcount' => $totalcount);
    }

    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        switch (get_class($_record)) {
            case 'Sipgate_Model_Account':
                $filter = new Sipgate_Model_LineFilter(array(), 'AND');
                $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'account_id', 'operator' => 'equals', 'value' => $_record->id)));
                $_record->lines = $this->_multipleRecordsToJson(Sipgate_Controller_Line::getInstance()->search($filter));
                break;
            case 'Sipgate_Model_Line':
                Tinebase_User::getInstance()->resolveUsers($_record, $this->_resolveUserFields['Sipgate_Model_Line']);
                $_record->account_id = Sipgate_Controller_Account::getInstance()->get($_record->account_id);
                break;
        }

        return parent::_recordToJson($_record);
    }

    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = null)
    {
        switch ($_records->getRecordClassName()) {
            case 'Sipgate_Model_Connection':
                Sipgate_Controller_Line::getInstance()->resolveMultipleLines($_records);
                $this->_resolveMultipleContacts($_records);
                break;
            case 'Sipgate_Model_Line':
                Sipgate_Controller_Account::getInstance()->resolveMultipleAccounts($_records);
                Tinebase_User::getInstance()->resolveMultipleUsers($_records, $this->_resolveUserFields['Sipgate_Model_Line']);
                break;
        }
        return parent::_multipleRecordsToJson($_records);
    }

    /**
     * resolves mc
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveMultipleContacts(Tinebase_Record_RecordSet $_records)
    {
        $contactIds = array_unique($_records->contact_id);
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($contactIds);
        foreach ($_records as $record) {
            $idx = $contacts->getIndexById($record->contact_id);
            if($idx) {
                $record->contact_id = $contacts[$idx];
            } else {
                $record->contact_id = NULL;
            }
        }
    }

    /**
     * dial number
     *
     * @param String $lineId line id
     * @param String $number phone number
     * @return Array
     */
    public function dialNumber($lineId = NULL, $number = NULL) {
        $ret = Sipgate_Controller_Line::getInstance()->dialNumber($lineId, $number);
        $ret['line'] = $this->_recordToJson($ret['line']);
        return $ret;
    }

    /**
     * close Session
     *
     * @param string $sessionId
     * @param array $line
     * @return array
     */
    public function closeSession($sessionId, $line) {
        return Sipgate_Controller_Line::getInstance()->closeSession($sessionId, $line);
    }

    /**
     * get Session Status
     *
     * @param string $sessionId
     * @param array $line
     * @return array
     */
    public function getSessionStatus($sessionId, $line) {
        if(empty($sessionId)) {
            throw new Sipgate_Exception('You have to submit a valid Session-ID!');
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug('getting Status ' . $sessionId);
        return Sipgate_Controller_Line::getInstance()->getSessionStatus($sessionId, $line);
    }
}
