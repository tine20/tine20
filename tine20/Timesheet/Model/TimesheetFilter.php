<?php
/**
 * Tine 2.0
 * 
 * @package     Timesheet
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * contract filter Class
 * @package     Timesheet
 */
class Timesheet_Model_TimesheetFilter extends Tinebase_Record_AbstractFilter
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Timesheet';
    
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
        ));
        
        // define query fields
        $this->_queryFields = array(
            'description',
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }    
}
