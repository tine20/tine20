<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Tree_NodePathFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 */
class Tinebase_Model_Tree_NodePathFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       // value is expected to represent a single container
        1 => 'in',           // value is expected to be an array of container representations
    );
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * 
     * @todo implement
     */
    public function appendFilterSql($_select, $_backend)
    {
    }    
}
