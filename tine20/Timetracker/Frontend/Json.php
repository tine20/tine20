<?php
/**
 * Tine 2.0
 * @package     Timetracker
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * This class handles all Json requests for the Timetracker application
 *
 * @package     Timetracker
 * @subpackage  Frontend
 */
class Timetracker_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * timesheet controller
     *
     * @var Timetracker_Controller_Timesheet
     */
    protected $_timesheetController = NULL;

    /**
     * timesheet controller
     *
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = NULL;

    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array('Timetracker_Model_Timeaccount');

    /**
     * default model (needed for application starter -> defaultContentType)
     * @var string
     */
    protected $_defaultModel = 'Timesheet';

    /**
     * All configured models
     * @var array
     */
	protected $_configuredModels = array('Timesheet', 'Timeaccount');

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
    }

    /************************************** protected helper functions **************************************/

    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        switch (get_class($_record)) {
            case 'Timetracker_Model_Timesheet':
                $_record['timeaccount_id'] = $_record['timeaccount_id'] ? $this->_timeaccountController->get($_record['timeaccount_id']) : $_record['timeaccount_id'];
                $_record['timeaccount_id']['account_grants'] = Timetracker_Controller_Timeaccount::getInstance()->getGrantsOfAccount(Tinebase_Core::get('currentAccount'), $_record['timeaccount_id']);
                $_record['timeaccount_id']['account_grants'] = $this->_resolveTimesheetGrantsByTimeaccountGrants($_record['timeaccount_id']['account_grants'], $_record['account_id']);
                Tinebase_User::getInstance()->resolveUsers($_record, 'account_id');

                if (Tinebase_Core::getUser()->hasRight('Sales', 'manage_invoices') && ! empty($_record['invoice_id'])) {
                    try {
                        $_record['invoice_id'] = Sales_Controller_Invoice::getInstance()->get($_record['invoice_id']);
                    } catch (Tinebase_Exception_NotFound $nfe) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not resolve invoice with id ' . $_record['invoice_id']);
                    }
                }
                
                $recordArray = parent::_recordToJson($_record);
                break;
            default:
                $recordArray = parent::_recordToJson($_record);
        }

        return $recordArray;
    }

    /**
     * returns multiple records prepared for json transport
     * NOTE: we can't use parent::_multipleRecordsToJson here because of the different container handling
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
     * @param Tinebase_Model_Filter_FilterGroup
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     * 
     * @todo replace with Timetracker_Convert_Timesheet_Json
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }

        switch ($_records->getRecordClassName()) {
            case 'Timetracker_Model_Timesheet':
                // resolve timeaccounts
                $timeaccountIds = $_records->timeaccount_id;
                $timeaccounts = $this->_timeaccountController->getMultiple(array_unique(array_values($timeaccountIds)));
                
                $invoices = FALSE;
                
                Timetracker_Controller_Timeaccount::getInstance()->getGrantsOfRecords($timeaccounts, Tinebase_Core::get('currentAccount'));

                foreach ($_records as $record) {
                    $idx = $timeaccounts->getIndexById($record->timeaccount_id);
                    if ($idx !== FALSE) {
                        $record->timeaccount_id = $timeaccounts[$idx];
                        $record->timeaccount_id->account_grants = $this->_resolveTimesheetGrantsByTimeaccountGrants($record->timeaccount_id->account_grants, $record->account_id);
                    } else {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not resolve timeaccount (id: ' . $record->timeaccount_id . '). No permission?');
                    }
                }

                // resolve user afterwards because we compare ids in _resolveTimesheetGrantsByTimeaccountGrants()
                Tinebase_User::getInstance()->resolveMultipleUsers($_records, array('account_id', 'created_by', 'last_modified_by'), true);

                break;
            case 'Timetracker_Model_Timeaccount':
                $converter = new Timetracker_Convert_Timeaccount_Json();
                $result = $converter->fromTine20RecordSet($_records, $_filter, $_pagination);
                return $result;
        }
        
        if (Tinebase_Core::getUser()->hasRight('Sales', 'manage_invoices')) {
            $invoiceIds = array_unique(array_values($_records->invoice_id));
            $invoices   = Sales_Controller_Invoice::getInstance()->getMultiple($invoiceIds);
            
            foreach ($_records as $record) {
                if ($invoices && $record->invoice_id) {
                    $record->invoice_id = $invoices->getById($record->invoice_id);
                }
            }
        }
        
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        $_records->setTimezone(Tinebase_Core::getUserTimezone());
        $_records->setConvertDates(true);
        $result = $_records->toArray();

        return $result;
    }

    /**
     * calculate effective ts grants so the client doesn't need to calculate them
     *
     * @param  array  $TimeaccountGrantsArray
     * @param  int    $timesheetOwnerId
     * @return array
     */
    protected function _resolveTimesheetGrantsByTimeaccountGrants($timeaccountGrantsArray, $timesheetOwnerId)
    {
        $manageAllRight = Timetracker_Controller_Timeaccount::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE);
        $currentUserId = Tinebase_Core::getUser()->getId();

        $modifyGrant = $manageAllRight || ($timeaccountGrantsArray[Timetracker_Model_TimeaccountGrants::BOOK_OWN]
            && $timesheetOwnerId == $currentUserId) || $timeaccountGrantsArray[Timetracker_Model_TimeaccountGrants::BOOK_ALL];

        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_READ]   = true;
        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_EDIT]   = $modifyGrant;
        $timeaccountGrantsArray[Tinebase_Model_Grants::GRANT_DELETE] = $modifyGrant;

        return $timeaccountGrantsArray;
    }

    /**
     * Return registry data for timeaccount favorites
     *
     * @return array
     * @throws \Tinebase_Exception_InvalidArgument
     */
    public function getTimeAccountFavoriteRegistry()
    {
        $appPrefs = Tinebase_Core::getPreference($this->_applicationName);

        // Get preference
        $quickTagPreferences = $appPrefs->search(
            new Tinebase_Model_PreferenceFilter([
                'name' => Timetracker_Preference::QUICKTAG
            ])
        );

        // There could be only one result, if not do nothing.
        if ($quickTagPreferences->count() !== 1) {
            return null;
        }

        $quickTagPreference = $quickTagPreferences->getFirstRecord();

        if ($quickTagPreference->value === false) {
            return null;
        }

        // Resolve tag by it's id
        $tag = Tinebase_Tags::getInstance()->get($quickTagPreference->value);

        $pref = array();
        $pref['quicktagId'] = $quickTagPreference->value;
        $pref['quicktagName'] = $tag->name;

        return $pref;
    }

    /**
     * Return registry data
     *
     * @return array
     * @throws \Tinebase_Exception_InvalidArgument
     */
    public function getRegistryData()
    {
        $registry = [];

        if (Timetracker_Config::getInstance()->featureEnabled(Timetracker_Config::FEATURE_TIMEACCOUNT_BOOKMARK)) {
            $registry = array_merge($registry, $this->getOwnTimeAccountBookmarks());
        }

        $timeaccountFavorites = $this->getTimeAccountFavoriteRegistry();

        if ($timeaccountFavorites !== null) {
            $registry = array_merge($registry, $this->getTimeAccountFavoriteRegistry());
        }

        return $registry;
    }

    /**
     * @return array
     */
    protected function getOwnTimeAccountBookmarks()
    {
        $ownFavoritesFilter = new Timetracker_Model_TimeaccountFavoriteFilter([
            'account_id' => Tinebase_Core::getUser()->accountId,
        ]);

        $timeAccountFavs = Timetracker_Controller_TimeaccountFavorites::getInstance()->search($ownFavoritesFilter);
        $timeAccountFavsArray = [];

        foreach($timeAccountFavs as $timeAccountFav) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($timeAccountFav->timeaccount_id);

            // timeaccount will be used to set the defaults for opening new timesheet record in frontend
            // Resolve here to save loading time
            $timeAccountFavsArray[] = [
                'timeaccount' => $timeaccount->toArray(),
                'favId' => $timeAccountFav->id,
                'text' => $timeaccount->title,
                'leaf' => true,
                'iconCls' => 'task'
            ];
        }

        $pref = array();
        $pref['timeaccountFavorites'] = $timeAccountFavsArray;

        return $pref;
    }

    /************************************** public API **************************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchTimesheets($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_timesheetController, 'Timetracker_Model_TimesheetFilter', true);
    }

    /**
     * do search count request only when resultset is equal
     * to $pagination->limit or we are not on the first page
     *
     * @param $filter
     * @param $pagination
     * @param Tinebase_Controller_SearchInterface $controller the record controller
     * @param $totalCountMethod
     * @param integer $resultCount
     * @return array
     */
    protected function _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount)
    {
        if ($controller instanceof Timetracker_Controller_Timesheet) {
            $result = $controller->searchCount($filter);

            $totalresult = [];

            // add totalcounts of leadstates/leadsources/leadtypes
            $totalresult['totalcountbillable'] = $result['sum_is_billable_combined'];
            $totalresult['totalsum'] = $result['sum_accounting_time'];
            $totalresult['totalsumbillable'] = $result['sum_accounting_time_billable'];
            $totalresult['totalcount'] = $result['count'];

            return $totalresult;
        } else {
            return parent:: _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount);
        }
    }
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimesheet($id)
    {
        return $this->_get($id, $this->_timesheetController);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  array $context
     * @return array created/updated record
     */
    public function saveTimesheet($recordData, array $context = array())
    {
        $this->_timesheetController->setRequestContext($context);
        return $this->_save($recordData, $this->_timesheetController, 'Timesheet');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteTimesheets($ids)
    {
        return $this->_delete($ids, $this->_timesheetController);
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchTimeaccounts($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_timeaccountController, 'Timetracker_Model_TimeaccountFilter', true);
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getTimeaccount($id)
    {
        return $this->_get($id, $this->_timeaccountController);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveTimeaccount($recordData)
    {
        return $this->_save($recordData, $this->_timeaccountController, 'Timeaccount');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteTimeaccounts($ids)
    {
        return $this->_delete($ids, $this->_timeaccountController);
    }

    /**
     * Add given timeaccount id as a users favorite
     *
     * @param $timeaccountId
     * @return Timetracker_Model_Timeaccount
     */
    public function addTimeAccountFavorite($timeaccountId)
    {
        $timeaccount = new Timetracker_Model_TimeaccountFavorite();
        $timeaccount->timeaccount_id = $timeaccountId;
        $timeaccount->account_id = Tinebase_Core::getUser()->accountId;

        Timetracker_Controller_TimeaccountFavorites::getInstance()->create($timeaccount);

        return $this->getOwnTimeAccountBookmarks();
    }

    /**
     * Delete given timeaccount favorite
     *
     * @param $favId
     * @return Tinebase_Record_RecordSet
     * @throws \Tinebase_Exception
     */
    public function deleteTimeAccountFavorite($favId)
    {
        Timetracker_Controller_TimeaccountFavorites::getInstance()->delete([
            $favId
        ]);

        return $this->getOwnTimeAccountBookmarks();
    }
}
