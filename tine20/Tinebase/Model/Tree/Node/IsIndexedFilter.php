<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Tree_Node_IsIndexedFilter
 *
 * filters for tree nodes that are indexed or not
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Tree_Node_IsIndexedFilter extends Tinebase_Model_Filter_Bool
{
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();

        // prepare value
        $op = $this->_value ? ' = ' : ' <> ';

        $quotedField1 = $db->quoteIdentifier('tree_fileobjects.indexed_hash');
        $quotedField2 = $db->quoteIdentifier('tree_filerevisions.hash');
        $_select->where($quotedField1 . $op . $quotedField2);
    }
}