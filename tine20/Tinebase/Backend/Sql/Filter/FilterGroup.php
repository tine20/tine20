<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
     * @param  boolean                           $_appendFilterSql
     */
    public static function appendFilters($_select, $_filters, $_backend, $_appendFilterSql = TRUE)
    {
        // support for direct sql filter append in derived filter groups
        if ($_appendFilterSql && method_exists($_filters, 'appendFilterSql')) {
            $_filters->appendFilterSql($_select, $_backend);
        }
        
        foreach ($_filters->getFilterObjects() as $filter) {
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            
            if ($filter instanceof Tinebase_Model_Filter_Abstract) {
                $filter->appendFilterSql($groupSelect, $_backend);
            } else {
                self::appendFilters($groupSelect, $filter, $_backend);
            }
            
            $groupSelect->appendWhere($_filters->getCondition());
        }
    }
}
