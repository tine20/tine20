<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * timeaccount filter Class
 * @package     Timetracker
 */
class Timetracker_Model_TimeaccountFilter extends Tinebase_Record_AbstractFilter
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Timetracker';
    
    /**
     * the constructor
     * it is needed because we have more validation fields in Tasks
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     * 
     * @todo    add more validators/filters
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array_merge($this->_validators, array(
            'description'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // 'special' defines a filter rule that doesn't fit into the normal operator/opSqlMap model 
            'showClosed'           => array('allowEmpty' => true, 'InArray' => array(true,false), 'special' => TRUE),
            'isBookable'           => array('allowEmpty' => true, 'InArray' => array(true,false), 'special' => TRUE),
        ));
        
        // define query fields
        $this->_queryFields = array(
            'number',
            'title'
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }    

   /**
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendFilterSql($_select)
    {
        $db = Tinebase_Core::getDb();
        
        if(isset($_this->showClosed) && $_this->showClosed){
            // nothing to filter
        } else {
            $_select->where($db->quoteIdentifier('is_open') . ' = 1');
        }
        
        // add container filter
        if (!empty($this->container) && is_array($this->container)) {
            $_select->where($db->quoteInto($db->quoteIdentifier('container_id') . ' IN (?)', $this->container));
        }
        
        parent::appendFilterSql($_select);
    }
}
