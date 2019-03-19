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
 * Tinebase_Model_Filter_Text
 *
 * filters one filterstring in one property
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_StrictFullText extends Tinebase_Model_Filter_FullText
{
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        // mysql supports full text for InnoDB as of 5.6
        if (Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4 | mariadb >= 10.0.5') &&
                Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_FULLTEXT_INDEX)) {
            parent::appendFilterSql($_select, $_backend);
        } else {
            if (Setup_Core::isLogLevel(Zend_Log::NOTICE)) Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                ' full text search is only supported on mysql 5.6.4+ / mariadb 10.0.5+ ... do yourself a favor and migrate. This query now maybe very slow for larger amount of data!');
        }

        $filterGroup = new Tinebase_Model_Filter_FilterGroup();

        if (!is_array($this->_value)) {
            $this->_value = array($this->_value);
        }
        foreach ($this->_value as $value) {
            $filter = new Tinebase_Model_Filter_Text($this->_field, 'contains', $value);
            $filterGroup->addFilter($filter);
        }

        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filterGroup, $_backend);
    }
}