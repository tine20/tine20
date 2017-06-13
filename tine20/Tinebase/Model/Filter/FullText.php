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
 * Tinebase_Model_Filter_FullText
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
        1 => 'notcontains',
        2 => 'equals',
        3 => 'not',
        4 => 'startswith',
        5 => 'endswith',
        6 => 'notin',
        7 => 'in',
        8 => 'wordstartswith'
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

        $not = false;
        if (false !== strpos($this->_operator, 'not')) {
            $not = true;
        }
        if ($this->_operator !== 'contains' && $this->_operator !== 'notcontains') {
            if (true === $not) {
                $this->_operator = 'notcontains';
            } else {
                $this->_operator = 'contains';
            }
        }

        $values = array();
        foreach ((array)$this->_value as $value) {
            //replace full text meta characters
            //$value = str_replace(array('+', '-', '<', '>', '~', '*', '(', ')', '"'), ' ', $value);
            $value = preg_replace('#\W#u', ' ', $value);
            // replace multiple spaces with just one
            $value = preg_replace('# +#u', ' ', trim($value));
            $values = array_merge($values, explode(' ', $value));
        }

        // mysql supports full text for InnoDB as of 5.6.4
        // full text can't do a pure negative search...
        if (true === $not || ! Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4') ) {

            if (true !== $not && Setup_Core::isLogLevel(Zend_Log::NOTICE)) Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                ' full text search is only supported on mysql/mariadb 5.6.4+ ... do yourself a favor and migrate. This query now maybe very slow for larger amount of data!');

            $values = array_filter($values, function($val) {
                return mb_strlen($val) >= 3;
            });

            if (count($values) === 0) {
                $_select->where('1 = 1');
                return;
            }

            $filterGroup = new Tinebase_Model_Filter_FilterGroup();

            foreach ($values as $value) {
                $filter = new Tinebase_Model_Filter_Text($this->_field, $this->_operator, $value, isset($this->_options['tablename']) ? array('tablename' => $this->_options['tablename']) : array());
                $filterGroup->addFilter($filter);
            }

            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filterGroup, $_backend);

        } else {
            $ftConfig = $this->getMySQLFullTextConfig();
            $values = array_filter($values, function($val) use ($ftConfig) {
                return mb_strlen($val) >= $ftConfig['tokenSize'] && !in_array($val, $ftConfig['stopWords']);
            });

            if (count($values) === 0) {
                $_select->where('1 = 1');
                return;
            }

            $field = $this->_getQuotedFieldName($_backend);
            $searchTerm = '';

            foreach ($values as $value) {
                $searchTerm .= ($searchTerm !== '' ? ', ' : '') . '+' . $value . '*';
            }

            $_select->where('MATCH (' . $field . $db->quoteInto(') AGAINST (? IN BOOLEAN MODE)', $searchTerm));
        }
    }

    /**
     * this looks up the default mysql innodb full text stop word list and returns the contents as array
     *
     * TODO implement retrieving non default / configured stop word tables
     *
     * @return array
     */
    protected function getMySQLFullTextConfig()
    {
        $cacheId = 'mysqlFullTextConfig';

        try {
            return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __METHOD__, $cacheId, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {}

        $db = Tinebase_Core::getDb();

        $result['stopWords'] = $db->query('SELECT `value` FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD')->fetchAll(Zend_Db::FETCH_COLUMN, 0);
        $result['tokenSize'] = $db->query('SELECT @@innodb_ft_min_token_size')->fetchColumn(0);

        Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __METHOD__, $cacheId, $result, Tinebase_Cache_PerRequest::VISIBILITY_SHARED);

        return $result;
    }
}
