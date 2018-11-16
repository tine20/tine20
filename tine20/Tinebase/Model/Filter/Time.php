<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Time
 * 
 * filters time in one property
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Time extends Tinebase_Model_Filter_Text
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'before',
        2 => 'after',
        3 => 'isnull',
        4 => 'notnull',
        5 => 'before_or_equals',
        6 => 'after_or_equals'
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'            => array('sqlop' => ' = ?'),
        'before'            => array('sqlop' => ' < ?'),
        'after'             => array('sqlop' => ' > ?'),
        'isnull'            => array('sqlop' => ' IS NULL'),
        'notnull'           => array('sqlop' => ' IS NOT NULL'),
        'before_or_equals'  => array('sqlop' => ' <= ?'),
        'after_or_equals'   => array('sqlop' => ' >= ?'),
    );
}
