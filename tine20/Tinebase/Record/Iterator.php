<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */


/**
 * class Tinebase_Record_Iterator
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Iterator
{
    /**
     * class with function to call for each record
     * 
     * @var Tinebase_Record_IteratableInterface
     */
//    protected $_iteratable = NULL;
    
    protected $_function = array();
    
    /**
     * controller with search fn
     * 
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controller = NULL;
    
    /**
     * filter group
     * 
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_filter = NULL;
    
    protected $_start = 0;
    
    protected $_options = array(
        'limit'		=> 100,
    );
    
    /**
    * the constructor
    *
    * @param array $_params
    */
    public function __construct($_params)
    {
        $requiredParams = array('controller', 'filter', 'function');
        foreach ($requiredParams as $param) {
            if (isset($_params[$param])) {
                $this->{'_' . $param} = $_params[$param];
            } else {
                throw new Tinebase_Exception_InvalidArgument($param . ' required');
            }
        }
        
        if (isset($_params['options'])) {
            $this->_options = array_merge($this->_options, $_params['options']);
        }
        
//         if (isset($_params['iteratable'])) {
//             $this->_iteratable = $_params['iteratable'];
//         } else {
//             throw new Tinebase_Exception_InvalidArgument('iteratable required');
//         }
    }
    
    public function iterate($_params)
    {
//         $start = 0;
//         $limit = 100;
//         $records = $this->_getRecords($start, $limit);
//         if (count($records) < 1) {
//             return FALSE;
//         }
        
//         $filename = $this->_getFilename();
//         $filehandle = ($this->_toStdout) ? STDOUT : fopen($filename, 'w');
        
//         $fields = $this->_getFields($records->getFirstRecord());
//         self::fputcsv($filehandle, $fields);
        
//         $totalcount = count($records);
//         while (count($records) > 0) {
//             $this->_addBody($filehandle, $records, $fields);
        
//             $start += $limit;
//             $records = $this->_getRecords($start, $limit);
//             $totalcount += count($records);
//         }
    }
    
    /**
    * get records and resolve fields
    *
    * @param integer  $_start
    * @param integer  $_limit
    * @return Tinebase_Record_RecordSet
    */
    protected function _getRecords()
    {
//         // get records by filter (ensure export acl first)
//         if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting records using filter: ' . print_r($this->_filter->toArray(), TRUE));
//         $pagination = (! empty($this->_sortInfo)) ? new Tinebase_Model_Pagination($this->_sortInfo) : new Tinebase_Model_Pagination();
//         if ($_start !== NULL && $_limit !== NULL) {
//             $pagination->start = $_start;
//             $pagination->limit = $_limit;
//         }
//         $records = $this->_controller->search($this->_filter, $pagination, $this->_getRelations, FALSE, 'export');
    
//         if (count($records) > 0) {
//             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exporting ' . count($records) . ' records ...');
    
//             $this->_resolveRecords($records);
//             $records->setTimezone(Tinebase_Core::get('userTimeZone'));
    
//             //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($records->toArray(), TRUE));
//         }
    
//         return $records;
    }
}
