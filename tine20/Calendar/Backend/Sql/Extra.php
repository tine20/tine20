<?php

/**
 * this class provides access to the sql table <prefix>_cal_extra
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * this class provides access to the sql table <prefix>_cal_extra
 * 
 * @package     Calendar
 */
class Calendar_Backend_Sql_Extra extends Zend_Db_Table_Abstract
{
    protected $_referenceMap = array(
        'Events' => array(
            'columns'       => 'cal_id',
            'refTableClass' => 'Calendar_Backend_Sql_Events',
            'refClumns'     => 'cal_id'
        )
    );
    
    
}