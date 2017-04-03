<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
class Tinebase_Model_Filter_FullText extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'contains',
    );

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_select->getAdapter();

        // mysql supports full text for InnoDB as of 5.6
        if ( ! Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4') ) {

            if (Setup_Core::isLogLevel(Zend_Log::NOTICE)) Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                ' full text search is only supported on mysql/mariadb 5.6.4+ ... do yourself a favor and migrate. This query now maybe very slow for larger amount of data!');

            $filterGroup = new Tinebase_Model_Filter_FilterGroup();

            if (!is_array($this->_value)) {
                $this->_value = array($this->_value);
            }
            foreach ($this->_value as $value) {
                //replace full text meta characters
                //$value = str_replace(array('+', '-', '<', '>', '~', '*', '(', ')', '"'), ' ', $value);
                $value = preg_replace('#\W#u', ' ', $value);
                // replace multiple spaces with just one
                $value = preg_replace('# +#u', ' ', trim($value));
                $values = explode(' ', $value);
                foreach($values as $value) {
                    $filter = new Tinebase_Model_Filter_Text($this->_field, 'contains', $value, isset($this->_options['tablename']) ? array('tablename' => $this->_options['tablename']) : array());
                    $filterGroup->addFilter($filter);
                }
            }

            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filterGroup, $_backend);
        } else {

            $field = $this->_getQuotedFieldName($_backend);

            $searchTerm = '';
            if (!is_array($this->_value)) {
                $this->_value = array($this->_value);
            }
            foreach ($this->_value as $value) {
                //replace full text meta characters
                //$value = str_replace(array('+', '-', '<', '>', '~', '*', '(', ')', '"'), ' ', $value);
                $value = preg_replace('#\W#u', ' ', $value);
                // replace multiple spaces with just one
                $value = preg_replace('# +#u', ' +', trim($value));
                $searchTerm .= ($searchTerm === '' ? '+' : ' +') . $value . '*';
            }

            $_select->where('MATCH (' . $field . $db->quoteInto(') AGAINST (? IN BOOLEAN MODE)', $searchTerm));
        }
    }
}
