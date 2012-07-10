<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Record_Iterator
 * 
 * this helper class allows to iterate through batches of records (by default 100 records/iteration).
 * it is required when big amounts of records needs to be processed as this requires lots of memory if no iterator is used.
 * 
 * use it like this:
       $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,              // should implement Tinebase_Record_IteratableInterface
            'controller' => $this->_controller, // Tinebase_Controller_Record_Abstract
            'filter'     => $this->_filter,     // Tinebase_Model_Filter_FilterGroup
            'options'     => array(
                // add specific options here
            ),
        ));
        $totalcount = $iterator->iterate();
 *
 * the calling class should implement processIteration($_records) that is given the batch of records to process.
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
    protected $_iteratable = NULL;

    /**
     * the function name to call in each iteration
     * 
     * @var string
     */
    protected $_function = 'processIteration';

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

    /**
     * record ids
     * 
     * @var array
     */
    protected $_recordIds = NULL;
    
    /**
     * options array
     * 
     * @var array
     */
    protected $_options = array(
        'limit'            => 100,
        'searchAction'    => 'get',
        'sortInfo'        => NULL,
        'getRelations'  => FALSE,
    );

    /**
     * the constructor
     *
     * @param array $_params
     * 
     * @todo check interfaces
     */
    public function __construct($_params)
    {
        $requiredParams = array('controller', 'filter', 'iteratable', 'function');
        foreach ($requiredParams as $param) {
            if (isset($_params[$param])) {
                $this->{'_' . $param} = $_params[$param];
            } else if ($param !== 'function') {
                throw new Tinebase_Exception_InvalidArgument($param . ' required');
            }
        }
        
        if ($this->_function === 'processIteration' && ! $this->_iteratable instanceof Tinebase_Record_IteratableInterface) {
            throw new Tinebase_Exception_InvalidArgument('iteratable needs to implement Tinebase_Record_IteratableInterface');
        }

        if (isset($_params['options'])) {
            $this->_options = array_merge($this->_options, $_params['options']);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Created new Iterator with options: ' . print_r($this->_options, TRUE));
    }

    /**
     * iterator batches of records
     * 
     * @return array with totalcount and results in array
     */
    public function iterate()
    {
        $records = $this->_getRecords();
        if (count($records) < 1) {
            return FALSE;
        }

        $result = array(
            'totalcount' => count($records),
            'results'    => array(),
        );
        
        while (count($records) > 0) {
            $arguments = func_get_args();
            array_unshift($arguments, $records);
            $result['results'][] = call_user_func_array(array($this->_iteratable, $this->_function), $arguments);

            $records = $this->_getRecords();
            $result['totalcount'] += count($records);
        }
        
        return $result;
    }

    /**
     * get records and resolve fields
     *
     * @return Tinebase_Record_RecordSet
     */
    protected function _getRecords()
    {
        if ($this->_recordIds === NULL) {
            // need to fetch record ids first because filtered fields can change during iteration step 
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Getting record ids using filter: ' . print_r($this->_filter->toArray(), TRUE));
            $this->_recordIds = $this->_controller->search($this->_filter, NULL, FALSE, TRUE, $this->_options['searchAction']);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found ' . count($this->_recordIds) . ' total records to process.');
            if (empty($this->_recordIds)) {
                return new Tinebase_Record_RecordSet($this->_filter->getModelName());
            }
        }

        $pagination = (! empty($this->_options['sortInfo'])) ? new Tinebase_Model_Pagination($this->_options['sortInfo']) : new Tinebase_Model_Pagination();

        // get records by filter (ensure acl)
        $filterClassname = get_class($this->_filter);
        $recordIdsForIteration = array_splice($this->_recordIds, 0, $this->_options['limit']);
        $idFilter = new $filterClassname(array(
            array('field' => array_key_exists('idProperty', $this->_options) ? $this->_options['idProperty'] : 'id', 'operator' => 'in', 'value' => $recordIdsForIteration)
        ));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Getting records using filter: ' . print_r($idFilter->toArray(), TRUE));
        $records = $this->_controller->search($idFilter, $pagination, $this->_options['getRelations']);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got ' . count($records) . ' for next iteration.');
        
        return $records;
    }
}
