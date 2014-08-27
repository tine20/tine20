<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Import Controller
 * 
 * @package Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_ScheduledImport extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Controller_ScheduledImport
     */
    private static $instance = null;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Tinebase';
        $this->_modelName = 'Tinebase_Model_Import';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'import',
        ));
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
        $this->_resolveCustomFields = false;
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Controller_ScheduledImport
     */
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Search and executed the next scheduled import
     */
    public function runNextScheduledImport()
    {
        if ($record = $this->_getNextScheduledImport()) {
            return $this->_doScheduledImport($record);
        }
        
        return NULL;
    }
    
    /**
     * Get the next scheduled import
     * 
     * @param interval
     * @param recursive
     * @return Object
     */
    protected function _getNextScheduledImport()
    {
        $timestampBefore = null;
        
        $now = new Tinebase_DateTime();
        
        $anHourAgo = clone $now;
        $anHourAgo->subHour(1);
        
        $aDayAgo = clone $now;
        $aDayAgo->subDay(1);
        
        $aWeekAgo = clone $now;
        $aWeekAgo->subWeek(1);
        
        $filter = new Tinebase_Model_ImportFilter(array(), 'OR');
        
        $filter0 = new Tinebase_Model_ImportFilter(array(
                array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_ONCE),
                array('field' => 'timestamp', 'operator' => 'before', 'value' => $now),
        ), 'AND');
        
        $filter1 = new Tinebase_Model_ImportFilter(array(
            array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_HOURLY),
            array('field' => 'timestamp', 'operator' => 'before', 'value' => $anHourAgo),
        ), 'AND');
        
        $filter2 = new Tinebase_Model_ImportFilter(array(
                array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_DAILY),
                array('field' => 'timestamp', 'operator' => 'before', 'value' => $aDayAgo),
        ), 'AND');
        
        $filter3 = new Tinebase_Model_ImportFilter(array(
                array('field' => 'interval', 'operator' => 'equals', 'value' => Tinebase_Model_Import::INTERVAL_WEEKLY),
                array('field' => 'timestamp', 'operator' => 'before', 'value' => $aWeekAgo),
        ), 'AND');
        
        $filter->addFilterGroup($filter0);
        $filter->addFilterGroup($filter1);
        $filter->addFilterGroup($filter2);
        $filter->addFilterGroup($filter3);
        
        // Always sort by creation timestamp to ensure first in first out
        $pagination = new Tinebase_Model_Pagination(array(
            'limit'     => 1,
            'sort'      => 'timestamp',
            'dir'       => 'DESC'
        ));
        
        $record = $this->search($filter, $pagination)->getFirstRecord();
        
        if (! $record) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' No ScheduledImport could be found.');
            }
            
            return NULL;
        }
        return $record;
    }
    
    /**
     * Execute scheduled import
     * @param Tinebase_Model_Import $record
     * @return Tinebase_Model_Import
     */
    protected function _doScheduledImport(Tinebase_Model_Import $record = NULL)
    {
        $importer = $record->getOption('plugin');
        
        $options = array(
            'container_id' => $record->container_id,
            // legacy
            'importContainerId' => $record->container_id,
        );
        
        $handle = fopen($record->source, 'r');
        
        $importer = new $importer($options);
        
        if ($handle) {
            // do import
            $importer->import($handle);
            fclose($handle);
            
            switch ($record->interval) {
                case Tinebase_Model_Import::INTERVAL_DAILY:
                    $record->timestamp->addDay(1);
                    break;
                case Tinebase_Model_Import::INTERVAL_WEEKLY:
                    $record->timestamp->addWeek(1);
                    break;
                case Tinebase_Model_Import::INTERVAL_HOURLY:
                    $record->timestamp->addHour(1);
                    break;
                default:
                    $record->timestamp = $now;
            }
            
            // update record
            $record = $this->update($record);
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ . ' The source could not be loaded: "' . $record->source . '"');
            }
        }
        
        return $record;
    }
}