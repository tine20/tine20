<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Leads Filter Class
 * @package Crm
 */
class Crm_Model_LeadFilter extends Tinebase_Record_AbstractFilter
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Crm';
    
    /**
     * the constructor
     * it is needed because we have more validation fields in Tasks
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_validators = array_merge($this->_validators, array(
        // 'special' defines a filter rule that doesn't fit into the normal operator/opSqlMap model 
            'showClosed'           => array('allowEmpty' => true, 'InArray' => array(true,false), 'special' => TRUE),
            'probability'          => array('allowEmpty' => true, 'Int',    'special' => TRUE),
            'leadstate'            => array('allowEmpty' => true,           'special' => TRUE),
        ));
        
        // define query fields
        $this->_queryFields = array(
            'description',
            'lead_name',
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
        
        if(isset($this->showClosed) && $this->showClosed){
            // nothing to filter
        } else {
            $_select->where($db->quoteIdentifier('end') . ' IS NULL');
        }

        if (!empty($this->leadstate)) {
            $_select->where($this->_db->quoteInto($db->quoteIdentifier('leadstate_id') . ' = ?', $this->leadstate));
        }
        if (!empty($this->probability)) {
            $_select->where($this->_db->quoteInto($db->quoteIdentifier('probability') . ' >= ?', (int)$this->probability));
        }
        
        parent::appendFilterSql($_select);
    }    
    
    /**
     * sets the record related properties from user generated input.
     * 
     * overwrite this because we don't have the right filter structure in the crm yet
     *
     * @param array $_data            the new data to set
     * 
     * @todo    remove this when the crm filter toolbar has been updated to the general widget
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data, FALSE);        
    }
}
