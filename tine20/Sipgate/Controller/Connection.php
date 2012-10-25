<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contact controller for Sipgate
 *
 * @package     Sipgate
 * @subpackage  Controller
 */
class Sipgate_Controller_Connection extends Tinebase_Controller_Record_Abstract
{
    /**
     * prefix to remove from sip-uris
     * @var array
     */
    protected $_stripPrefix = array('2300','2301','2400','1100');

    /**
     * check for container ACLs
     * @var boolean
     */
    protected $_doContainerACLChecks = false;

    /**
     * @var Sipgate_Backend_Api
     */
    protected $_apiBackend = NULL;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     * @var boolean
     */
    protected $_doRightChecks = false;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Sipgate';
        $this->_modelName = 'Sipgate_Model_Connection';
        $this->_backend = new Sipgate_Backend_Connection();
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
     * @var Sipgate_Controller_Connection
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Sipgate_Controller_Connection
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sipgate_Controller_Connection();
        }

        return self::$_instance;
    }
    /**
     * sync line
     * @param Sipgate_Model_Line $_line
     * @param Tinebase_DateTime $_from
     * @param Tinebase_DateTime $_to
     * @param boolean $verbose
     */
    public function syncLine(Sipgate_Model_Line $_line, Tinebase_DateTime $_from = NULL, Tinebase_DateTime $_to = NULL, $verbose = false)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName('Sipgate');

        if (! Tinebase_Core::getUser()->hasRight($app->getId(), Sipgate_Acl_Rights::SYNC_LINES)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to sync lines!');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->debug('Synchronizing Line ' . $_line->sip_uri . '...');
        }

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $count = 0;

        if($_from == null) {
            if($_line->last_sync == null) {
                $_from = new Tinebase_DateTime();
                $_from->subMonth(2);
            } else {
                $_from = $_line->last_sync;
            }
        }

        $_to = $_to ? $_to : new Tinebase_DateTime();

        // timezone corrections
        $_from->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_to->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));

        if($verbose) {
            echo 'Syncing line ' . $_line->sip_uri . ' from ' . $_from->getIso() . ' to ' . $_to->getIso() . PHP_EOL;
        }

        try {
            if(!$this->_apiBackend) {
                $this->_apiBackend = Sipgate_Backend_Api::getInstance()->connect($_line->account_id, $sipgate_user, $sipgate_pwd);
            }
            $response = $this->_apiBackend->getCallHistory($_line->__get('sip_uri'), $_from, $_to, 0, 1000);
            if(is_array($response['history']) && $response['totalcount'] > 0) {
                $paging = new Tinebase_Model_Pagination();

                // find already synced entries
                foreach($response['history'] as $call) {
                    $entryIds[] = $call['EntryID'];
                }
                $filter = new Sipgate_Model_ConnectionFilter(array(), 'AND');
                $filter->addFilter(new Tinebase_Model_Filter_Id('entry_id', 'in', $entryIds));
                $result = Sipgate_Controller_Connection::getInstance()->search($filter, $paging, false, false);
                $oldEntries = array();
                foreach($result->getIterator() as $connection) {
                    $oldEntries[] = $connection->entry_id;
                }
                unset($result, $connection, $filter);
                $count = 0;
                foreach($response['history'] as $call) {
                    // skip sync if already in db
                    if(in_array($call['EntryID'], $oldEntries)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug('Skipping call Id' . $call['EntryID'] . '.');
                        }
                        continue;
                    }

                    $localNumber = $this->_getLocalNumber($call['LocalUri']);
                    $remoteNumber = $this->_getRemoteNumber($call['RemoteUri'], $localNumber, true);

                    $filter = new Addressbook_Model_ContactFilter(array(array("field" => "telephone", "operator" => "contains", "value" => $remoteNumber)));
                    $s = Addressbook_Controller_Contact::getInstance()->search($filter, $paging);

                    $now = new Tinebase_DateTime();
                    $connection = new Sipgate_Model_Connection(array(
                        'tos'           => $call['TOS'],
                        'entry_id'      => $call['EntryID'],
                        'local_uri'     => $call['LocalUri'],
                        'remote_uri'    => $call['RemoteUri'],
                        'status'        => $call['Status'],
                        'local_number'  => '+' . $localNumber,
                        'remote_number' => ($remoteNumber == 'anonymous' ) ? 'anonymous' : '+' . $remoteNumber,
                        'line_id'       => $_line->getId(),
                        'timestamp'     => strtotime($call['Timestamp']),
                        'creation_time' => $now,
                        // @TODO: also collect accounting info
                        //                     'tarif'         => $call['TariffName'],
                        //                     'duration'      => $call['Duration'],
                        //                     'units_charged' => $call['UnitsCharged'],
                        //                     'price_unit'    => $call['PricePerUnit']['TotalIncludingVat'],
                        //                     'price_total'   => $call['Price']['TotalIncludingVat'],
                        //                     'ticks_a'       => $call['TicksA'],
                        //                     'ticks_b'       => $call['TicksB'],
                        'contact_id'    => $s->getFirstRecord() ? $s->getFirstRecord()->getId() : null,
                        // when contact gets deleted save name
                        'contact_name'  => $s->getFirstRecord() ? $s->getFirstRecord()->n_fn : '',
                    ));
                    $count++;
                    $this->create($connection);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug('Creating call Id' . $call['EntryID'] . '.');
                    }
                }
            }
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }

        $_to->setTimezone('UTC');
        $_line->last_sync = $_to;
        Sipgate_Controller_Line::getInstance()->update($_line);

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->debug('Synchronizing Line ' . $_line->sip_uri . ' completed. Got ' . $count . ' new connections.');
        }

        return $count;
    }

    /**
     * you can define default filters here
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        $lines = Sipgate_Controller_Line::getInstance()->search(new Sipgate_Model_LineFilter());
        if($lines->count()) {
            $aclFilter = new Tinebase_Model_Filter_Text(array('field' => 'line_id', 'operator' => 'in', 'value' => $lines->id));
            $aclFilter->setIsImplicit(true);
            $_filter->addFilter($aclFilter);
        } else {
            $_filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'entry_id', 'operator' => 'equals', 'value' => 'impossible')));
        }
    }
    
    /**
     * returns the local number by the localUri
     */
    protected function _getLocalNumber($localUri) {
        return preg_replace('/\D*/i','', $localUri);
    }

    /**
     * returns the remote number by the remoteUri and localUri ()
     */
    protected function _getRemoteNumber($remoteUri, $localUri, $localUriIsCleaned = null)
    {
        preg_match('/sip:(\d+)(e\d+)?@.*/', $remoteUri, $m);

        if(!$localUriIsCleaned) {
            $localUri = $this->_getSourceNumber($localUri);
        }

        if(empty($m)) {
            // _('anonymous')
            $remoteNumber = 'anonymous';
        } elseif($m[2]) {    // called myself
            $remoteNumber = $localUri;
        } else {
            $remoteNumber = str_replace($this->_stripPrefix, '', $m[1]);
        }

        return $remoteNumber;
    }

    /**
     * synchronizes unassigned connections with contacts and clear old assignments
     * @param Sipgate_Model_Line/Tinebase_Record_RecordSet $_line just work on this line
     */
    public function syncContacts($_line = NULL)
    {
        $lines = NULL;

        if($_line) {
            if ($_line instanceof Sipgate_Model_Line) {
                $lines = new Tinebase_Record_RecordSet('Sipgate_Model_Line', array($_line->toArray()));
            } elseif ($_line instanceof Tinebase_Record_RecordSet) {
                $lines = $_line;
            } else {
                throw new Tinebase_Exception_InvalidArgument('$_line must be an instance of Tinebase_Record_RecordSet or Sipgate_Model_Line!');
            }
        }
        // remove old assignments
        $filter = new Sipgate_Model_ConnectionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contact_id', 'operator' => 'not', 'value' => NULL)));

        if($lines) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'line_id', 'operator' => 'in', 'value' => $lines->id)));
        }

        $connections = $this->search($filter);

        $contactIds = array_unique($connections->contact_id);
        $filter = new Addressbook_Model_ContactFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $contactIds)));
        $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);

        foreach($contactIds as $cid) {
            $contact = $contacts->filter('id', $cid)->getFirstRecord();
            $numbers = array_unique($connections->filter('contact_id', $cid)->remote_number);

            if($contact->n_fn != $connection->contact_name || (
                ! in_array($contact->tel_assistent, $numbers) &&
                ! in_array($contact->tel_car, $numbers) &&
                ! in_array($contact->tel_cell, $numbers) &&
                ! in_array($contact->tel_cell_private, $numbers) &&
                ! in_array($contact->tel_fax, $numbers) &&
                ! in_array($contact->tel_fax_home, $numbers) &&
                ! in_array($contact->tel_home, $numbers) &&
                ! in_array($contact->tel_other, $numbers) &&
                ! in_array($contact->tel_pager, $numbers) &&
                ! in_array($contact->tel_prefer, $numbers) &&
                ! in_array($contact->tel_work, $numbers)
            )) {
                $filter = new Sipgate_Model_ConnectionFilter(array());
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contact_id', 'operator' => 'equals', 'value' => $cid)));
                $this->updateMultiple($filter, array('contact_id' => null));
            }
        }

        // add new assignments
        $filter = new Sipgate_Model_ConnectionFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contact_id', 'operator' => 'equals', 'value' => null)));

        if($lines) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'line_id', 'operator' => 'in', 'value' => $lines->id)));
        }

        $connections = $this->search($filter);
        $numbers = array_unique($connections->remote_number);

        foreach($numbers as $number) {
            if($number != 'anonymous') {
                $filter = new Addressbook_Model_ContactFilter(array(array("field" => "telephone", "operator" => "contains", "value" => $number)));
                $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
                if($contact) {
                    $ids = $connections->filter('remote_number', $number)->id;
                    $filter = new Sipgate_Model_ConnectionFilter(array(array('field' => 'id', 'operator' => 'in', 'value' => $ids)));
                    $this->updateMultiple($filter, array('contact_id' => $contact->getId(), 'contact_name' => $contact->n_fn));
                }
            }
        }
    }

    /**
     * Syncs all lines for the current user
     */
    public function syncLines(Tinebase_DateTime $_from = NULL, Tinebase_DateTime $_to = NULL, $_shared = false, $verbose = false)
    {
        $filter = new Sipgate_Model_AccountFilter(array('field' => 'created_by', 'operator' => 'equals', 'value' => $_shared ? NULL : Tinebase_Core::getUser()->getId()));
        $pag = new Tinebase_Model_Pagination();
        $accounts = Sipgate_Controller_Account::getInstance()->search($filter, $pag);

        foreach($accounts->getIterator() as $account) {
            $filter = new Sipgate_Model_ConnectionFilter(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId()));
            $lines = Sipgate_Controller_Line::getInstance()->search($filter, $pag);

            foreach($lines->getIterator() as $line) {
                $count = $count + $this->syncLine($line, $_from, $_to, $verbose);
            }
        }
    }
}
