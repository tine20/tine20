<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Calendar_Model_RruleFilter extends Tinebase_Model_Filter_Text
{
    protected $_operators = [
        'in',
        'notin',
        'isnull',
        'notnull'
    ];

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     *
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (in_array($this->_operator, ['isnull', 'notnull'], true) || 0 === count($this->getValue())) {
            parent::appendFilterSql($_select, $_backend);
            return;
        }

        $value = $this->getValue();
        if (is_array($value) && is_array($value = array_shift($value))) {
            foreach ($value as $key => $val) {
                if (false === $val) {
                    continue;
                }
                if ('notin' === $this->_operator) {
                    $_select->where('rrule NOT LIKE ?', '%' . strtoupper($key) . '%');
                } else {
                    $_select->orWhere('rrule LIKE ?', '%' . strtoupper($key) . '%');
                }
            }
        }
    }
}