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
        ));
        
        // define query fields
        $this->_queryFields = array(
            'number',
            'title'
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }    
}
