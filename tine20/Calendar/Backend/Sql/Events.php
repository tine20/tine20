<?php

/**
 * this classes provides access to the sql table <prefix>_cal
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Calendar_Backend_Sql_Events extends Zend_Db_Table_Abstract
{
    protected $_primary = 'cal_id';
    
    protected $_dependentTables = array(
        'Calendar_Backend_Sql_Dates',
        'Calendar_Backend_Sql_Repeats',
        'Calendar_Backend_Sql_User',
        'Calendar_Backend_Sql_Extra'
    );
    
    
    /*
    public function __construct($_config)
    {
        $tableprefix = Zend_Registry::get('dbConfig')->get('tableprefix');
        $this->_name = $tableprefix. 'cal';
        $this->_dependentTables = array(
            $tableprefix. 'cal_dates',
            $tableprefix. 'cal_extra',
            $tableprefix. 'cal_repeats',
            
            
        );
        parent::__construct($_config);
    }
    */
}
