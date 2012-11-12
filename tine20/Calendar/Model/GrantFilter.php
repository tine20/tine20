<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Acl Filter
 * 
 * Manages calendar grant for search actions
 * 
 * 
 * Assuring event grants is a two stage process for search operations.
 *  1. limiting the query (mixture of grants and filter)
 *  2. transform event set (all events user has no read grant for need to be cleaned -> freebusy infos)
 * 
 * NOTE: The effective grants of the events get dynamically appended to the
 *       event rows in SQL (at the moment by the sql backend class.)
 *       As such there is no need to compute the effective grants in stage 2
 * 
 * NOTE: stage 2 is implicitly done in the models setFromArray
 * 
 * 
 * @package Calendar
 */
class Calendar_Model_GrantFilter extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'in',
    );
    
    /**
     * @var array One of these grants must be given
     */
    protected $_requiredGrants = NULL;
    
    /**
     * appends sql to given select statement
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_AccessDenied
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        
        if ($this->_requiredGrants === NULL) {
            throw new Tinebase_Exception_AccessDenied('No grants have been defined.');
        }
        
        // PostgreSQL does not support aliases in having statements
        // replace them with the real statements
        // return an array where each element has 3 items: 0 - table, 1 - field/expression, 2 - alias
        $columns = $_select->getPart(Zend_Db_Select::COLUMNS);
        
        // it gets an array with expressions used in having clause
        $grantStatements = array();
        foreach($columns as $column) {
            if (!empty($column[2])) {
                $grantStatements[$column[2]] = $column[1];
            }
        }
        
        // it uses into having clause expressions instead of aliases
        foreach ($this->_requiredGrants as $grant) {
            $_select->orHaving($db->quoteInto($db->quoteIdentifier($grantStatements[$grant]) . ' = ?', 1));
        }
    }
    
    /**
     * get required grants
     * 
     * @return array
     */
    public function getRequiredGrants()
    {
        return $this->_requiredGrants;
    }
    
    /**
     * set required grants
     * 
     * @param array $_grants
     * 
     * (non-PHPdoc)
     * @see tine20/Tinebase/Model/Filter/Tinebase_Model_Filter_AclFilter::setRequiredGrants()
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }
}
