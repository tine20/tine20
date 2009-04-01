<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

require_once 'GroupSelect.php';

/**
 * FilterGroup SQL Backend
 *
 * @package    Tinebase
 * @subpackage Filter
 */
class Tinebase_Backend_Sql_Filter_FilterGroup
{
    /**
     * appends tenor of filters to sql select object
     * 
     * NOTE: In order to archive nested filters we use the extended 
     *       Tinebase_Model_Filter_FilterGroup select object. This object
     *       appends all contained filters at once concated by the concetation
     *       operator of the filtergroup
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Model_Filter_FilterGroup $_filters
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public static function appendFilters($_select, $_filters, $_backend)
    {
        foreach ($_filters as $filter) {
            $groupSelect = new Tinebase_Model_Backend_Sql_GroupSelect($_select);
            $filter->appendFilterSql($groupSelect, $_backend);
            $groupSelect->appendWhere($this->_concatenationCondition);
        }
    }
}