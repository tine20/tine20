<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * foreign id filter
 * 
 * Expects:
 * - a record class in options->recordClassName
 * - a controller class in options->controllerClassName
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ForeignRecords extends Tinebase_Model_Filter_ForeignId
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_Filter_ForeignRecords';

    /**
     * @var null|Tinebase_Record_RecordSet
     */
    protected $_mappingRecords = null;

    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['refIdField'])) {
            throw new Tinebase_Exception_InvalidArgument('refIdField is required');
        }
        if (! isset($_options['filtergroup']) && isset($_options['recordClassName'])) {
            $_options['filtergroup'] = $_options['recordClassName'] . 'Filter';
        }
        if (! isset($_options['controller']) && isset($_options['controllerClassName'])) {
            $_options['controller'] = $_options['controllerClassName'];
        }
        parent::_setOptions($_options);
    }

    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (! is_array($this->_mappingRecords)) {
            $this->_mappingRecords = $this->_getController()->search($this->_filterGroup);
        }

        // TODO allow to configure id property or get it from model config
        $this->_field = 'id';

        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)',
            count($this->_mappingRecords) === 0
                ? new Zend_Db_Expr('NULL')
                : $this->_mappingRecords->{$this->_options['refIdField']}
        );
    }
}
