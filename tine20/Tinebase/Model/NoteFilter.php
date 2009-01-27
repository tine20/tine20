<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use new filter group
 */

/**
 *  notes filter class
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Model_NoteFilter extends Tinebase_Record_AbstractFilter
{    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

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
            'creation_time'          => array('allowEmpty' => true),
        // not used yet
            'record_id'              => array('allowEmpty' => true),
            'record_model'           => array('allowEmpty' => true),
            'record_backend'         => array('allowEmpty' => true),        
            'note_type_id'           => array('allowEmpty' => true),
        // 'special' defines a filter rule that doesn't fit into the normal operator/opSqlMap model 
            'created_by'             => array('allowEmpty' => true, 'special' => TRUE),
        ));
        
        // define query fields
        $this->_queryFields = array(
            'note',
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }    
    
    /**
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     * 
     * @todo add created_by filter (join with user table for that?)
     */
    public function appendFilterSql($_select)
    {
        parent::appendFilterSql($_select);
    }
}
