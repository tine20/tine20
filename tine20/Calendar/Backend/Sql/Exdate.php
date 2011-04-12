<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * native tine 2.0 events sql backend exdate class
 *
 * @package Calendar
 */
class Calendar_Backend_Sql_Exdate extends Tinebase_Backend_Sql_Abstract
{
    /**
     * event foreign key column
     */
    const FOREIGNKEY_EVENT = 'cal_event_id';
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'cal_exdate';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Calendar_Model_Exdate';

}
