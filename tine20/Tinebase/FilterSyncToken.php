<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * FilterSyncToken API class
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 */

class Tinebase_FilterSyncToken implements Tinebase_Controller_Interface
{
    /**
     * @var Tinebase_FilterSyncToken_Backend_Sql
     */
    protected $_backend;

    /**
     * @var self
     */
    static private $_instance;

    /**
     * Tinebase_Model_FilterSyncToken constructor.
     */
    protected function __construct()
    {
        $this->_backend = new Tinebase_FilterSyncToken_Backend_Sql();
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
       if (null === self::$_instance) {
           self::$_instance = new self();
       }

       return self::$_instance;
    }


    /**
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return string
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Zend_Db_Statement_Exception
     */
    public function getFilterSyncToken(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        // make sure we have a last_modified_time property
        /** @var Tinebase_Record_Interface $model */
        $model = $_filter->getModelName();
        if (null === ($mc = $model::getConfiguration())) {
            /** @var Tinebase_Record_Interface $record */
            $record = new $model([], true);
            if (!$record->has('last_modified_time')) {
                throw new Tinebase_Exception_Record_NotAllowed($model . ' does not have a last_modified_time');
            }
        } else {
            if (!$mc->hasField('last_modified_time')) {
                throw new Tinebase_Exception_Record_NotAllowed($model . ' does not have a last_modified_time');
            }
        }

        // get id => last_modified_time map
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = Tinebase_Core::getApplicationInstance($model);
        $data = $controller->search($_filter, null, false,
            [Tinebase_Backend_Sql_Abstract::IDCOL, 'last_modified_time']);


        $filterSyncToken = Tinebase_Helper::arrayHash($data, true);
        // attention, the search above may alter the filter! so we need to hash it after we executed it
        $filterHash = $_filter->hash();

        if (!$this->_backend->hasFilterSyncToken($filterHash, $filterSyncToken)) {
            $this->_backend->create(new Tinebase_Model_FilterSyncToken([
                'filterHash' => $filterHash,
                'filterSyncToken' => $filterSyncToken,
                'idLastModifiedMap' => $data,
            ], true));
        }

        return $filterSyncToken;
    }

    /**
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param $_fromToken
     * @param $_toToken
     * @return array|bool
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Zend_Db_Statement_Exception
     */
    public function getMigration(Tinebase_Model_Filter_FilterGroup $_filter, $_fromToken, $_toToken)
    {
        // got to execute the filter once to make sure it gets altered by the controller
        // (in case the controller does that) TODO improve this?
        $controller = Tinebase_Core::getApplicationInstance($_filter->getModelName());
        $controller->search($_filter, new Tinebase_Model_Pagination(['start' => 0, 'limit' => 1]), false, true);

        $filterHash = $_filter->hash();
        $result = [
            'updated'   => [],
        ];

        try {
            $fromData = $this->_backend->getFilterSyncToken($filterHash, $_fromToken);
            $toData = $this->_backend->getFilterSyncToken($filterHash, $_toToken);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }

        $fromMap = $fromData->idLastModifiedMap;
        $toMap = $toData->idLastModifiedMap;

        $result['added'] = array_keys(array_diff_key($toMap, $fromMap));
        $result['deleted'] = array_keys(array_diff_key($fromMap, $toMap));

        foreach(array_keys(array_intersect_key($fromMap, $toMap)) as $key) {
            if ($fromMap[$key] !== $toMap[$key]) {
                $result['updated'][] = $key;
            }
        }

        return $result;
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Statement_Exception
     * @return bool
     */
    public function cleanUp()
    {
        $filterSTC = Tinebase_Config::getInstance()->{Tinebase_Config::FILTER_SYNC_TOKEN};

        $deleted = $this->_backend->deleteByAge($filterSTC->{Tinebase_Config::FILTER_SYNC_TOKEN_CLEANUP_MAX_AGE});

        $deleted += $this->_backend->deleteByFilterMax($filterSTC
            ->{Tinebase_Config::FILTER_SYNC_TOKEN_CLEANUP_MAX_FILTER});

        $deleted += $this->_backend->deleteByMaxTotal($filterSTC
            ->{Tinebase_Config::FILTER_SYNC_TOKEN_CLEANUP_MAX_TOTAL});

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' deleted ' . $deleted . ' filter sync token');

        return true;
    }
}