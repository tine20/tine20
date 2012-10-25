<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contact controller for Sipgate
 *
 * @package     Sipgate
 * @subpackage  Controller
 */
class Sipgate_Controller_Line extends Tinebase_Controller_Record_Abstract
{
    /**
     * check for container ACLs
     *
     * @var boolean
     *
     */
    protected $_doContainerACLChecks = false;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     *
     * @var boolean
     */
    protected $_doRightChecks = false;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Sipgate';
        $this->_modelName = 'Sipgate_Model_Line';
        $this->_backend = new Sipgate_Backend_Line();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Sipgate_Controller_Line
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Sipgate_Controller_Line
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Controller_Line();
        }

        return self::$_instance;
    }

    /**
     * resolves multiple lines
     * 
     * @param Tinebase_Record_RecordSet $_records
     */
    public function resolveMultipleLines(Tinebase_Record_RecordSet $_records)
    {
        $lineIds = array_unique($_records->line_id);
        $lines = $this->getMultiple($lineIds);
        foreach ($_records as $record) {
            $idx = $lines->getIndexById($record->line_id);
            $record->line_id = $lines[$idx];
        }
    }

    /**
     * Account-Id of the sipgate account
     * 
     * @param String $accountId
     */
    public function syncAccount($accountId = NULL)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $account = Sipgate_Controller_Account::getInstance()->getResolved($accountId);

        $ret = Sipgate_Backend_Api::getInstance()->connect($account)->getAllDevices();
        $be = new Sipgate_Model_Line();

        $createdLines = new Tinebase_Record_RecordSet('Sipgate_Model_Line');
        $updatedLines = new Tinebase_Record_RecordSet('Sipgate_Model_Line');

        foreach($ret['devices'] as $device) {
            $filter = new Sipgate_Model_LineFilter(array(array('field' => 'sip_uri', 'operator' => 'equals', 'value' => $device['SipUri'])));
            $result = $this->search($filter);
            if($result->count() == 0) {
                $lineArray = array(
                    'account_id' => $accountId,
                    'user_id'    => Tinebase_Core::getUser()->getId(),
                    'sip_uri'    => $device['SipUri'],
                    'uri_alias'  => $device['UriAlias'],
                    'e164_out'   => $device['E164Out'],
                    'e164_in'    => json_encode($device['E164In']),
                    'tos'        => $device['TOS'][0],
                    'creation_time' => time(),
                    'last_sync' => null
                );

                $newLine = new Sipgate_Model_Line($lineArray);
                $newLine = $this->create($newLine);
                $createdLines->addRecord($newLine);
            } else {
                $updLine = $result->getFirstRecord();
                $updLine->uri_alias = $device['UriAlias'];
                $updLine->e164_out = $device['E164Out'];
                $updLine->e164_in  = json_encode($device['E164In']);
                $updLine->tos = $device['TOS'][0];
                $updatedLines->addRecord($this->update($updLine));
            }
        }

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        $updatedAccount = Sipgate_Controller_Account::getInstance()->get($accountId);

        return $updatedAccount;
    }
    
    /**
     * returns all Lines for the current User
     */
    public function getUsableLines() {
        $filter = new Sipgate_Model_LineFilter(array(array('field' => 'user_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())), 'AND');
        return $this->search($filter);
    }
    
    /**
     * Dials a number by the given lineId
     * 
     * @param string $lineId
     * @param string $number
     * @throws Sipgate_Exception_Backend
     */
    public function dialNumber($lineId = NULL, $number = NULL) {
        $line = $this->_getLineToOperateOn($lineId);

        if(!$number) {
            throw new Sipgate_Exception('Please use a number!');
        } elseif(strpos($number,'+') == 0) { // number has already the international format
            $number = 'sip:' . str_replace('+', '', $number) . '@sipgate.de';
        } elseif(substr($number, 0, 2) == '00') { // number has the international format with 00 prefixed
            $number = 'sip:+' . substr($number, 2) . '@sipgate.de';
        } elseif(strpos($number,'0') == 0) {// number has the national format
            $pref = new Sipgate_Preference();
            $number = 'sip:' . $pref->getValue(Sipgate_Preference::INTERNATIONAL_PREFIX) . substr($number, 1) . '@sipgate.de';
        } else {
            throw new Sipgate_Exception('The number yout tried to call can not be resolved properly!');
        }

        return array(
            'result' => Sipgate_Backend_Api::getInstance()->connect($line->account_id)->dialNumber($line->sip_uri, $number),
            'line'   => $line
        );
    }

    /**
     * closes session (call)
     * 
     * @param string $sessionId
     * @param array $line
     */
    public function closeSession($sessionId, $line)
    {
        $line = $this->_getLineToOperateOn($line['data']['id']);
        return Sipgate_Backend_Api::getInstance()->connect($line->account_id)->closeSession($sessionId);
    }

    /**
     * returns the session (call) status
     * 
     * @param string $sessionId
     * @param array $line
     */
    public function getSessionStatus($sessionId, $line)
    {
        $line = $this->_getLineToOperateOn($line['data']['id']);
        return Sipgate_Backend_Api::getInstance()->connect($line->account_id)->getSessionStatus($sessionId);
    }

    /**
     * sendds an sms
     * 
     * @param array $values
     * @param string $lineId
     * 
     * @return array
     */
    public function sendMessage($values, $lineId)
    {
        $line = $this->get($lineId);
        $response = Sipgate_Backend_Api::getInstance()->connect($line->account_id)->sendSms($values['own_number'], $values['recipient_number'], $values['message']);
        $ret = array('success' => false, 'result' => $response, 'values' => $values);
        
        if ($response['StatusCode'] == 200) {
            $ret['success'] = true;
        }
        
        return $ret;
    }

    /**
     * returns line by line id and checks if it belongs to the user
     * 
     * @param string $lineId
     * @throws Sipgate_Exception_Backend
     * 
     * @return Sipgate_Model_Line
     */
    protected function _getLineToOperateOn($lineId)
    {
        $line = $this->get($lineId);
        if(!$line->user_id == Tinebase_Core::getUser()->getId()) {
            throw new Sipgate_Exception_Backend('You have no access to this line!');
        }
        return $line;
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        if(!is_array($_ids)) {
            $_ids = array($_ids);
        }

        $filter = new Sipgate_Model_ConnectionFilter(array(), 'OR');

        foreach($_ids as $id) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'line_id', 'operator' => 'equals', 'value' => $id)));
        }
        Sipgate_Controller_Connection::getInstance()->deleteByFilter($filter);
        return $_ids;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::_addDefaultFilter()
     */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        $manageShared = Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_SHARED_ACCOUNTS);
        $managePrivate = Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_PRIVATE_ACCOUNTS);
        $manage = Tinebase_Core::getUser()->hasRight('Sipgate', Sipgate_Acl_Rights::MANAGE_ACCOUNTS);

        $fg = new Sipgate_Model_LineFilter(array(), 'OR');

        // fetch all assignes lines
        $my = new Tinebase_Model_Filter_Id(
            array('field' => 'user_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        );
        $my->setIsImplicit(true);
        $fg->addFilter($my);

        if($manage) {
            // fetch all private accounts of the user himself
            if($managePrivate) {
                $accountFilter = new Sipgate_Model_AccountFilter(array(), 'AND');
                $accountFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'private')));
                $r = Sipgate_Controller_Account::getInstance()->search($accountFilter);
                if($r->count()) {
                    $f = new Tinebase_Model_Filter_Id(array('field' => 'account_id', 'operator' => 'in', 'value' => $r->id));
                    $f->setIsImplicit(true);
                    $fg->addFilter($f);
                }
            }
            // fetch shared accounts
            if($manageShared) {
                $accountFilter = new Sipgate_Model_AccountFilter(array(), 'AND');
                $accountFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'type', 'operator' => 'equals', 'value' => 'shared')));
                $r = Sipgate_Controller_Account::getInstance()->searchRightsLess($accountFilter);
                if($r->count()) {
                    $f = new Tinebase_Model_Filter_Id(array('field' => 'account_id', 'operator' => 'in', 'value' => $r->id));
                    $f->setIsImplicit(true);
                    $fg->addFilter($f);
                }
            }
        }
        $_filter->addFiltergroup($fg);
    }
}
