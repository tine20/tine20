<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * foreign id filter
 * 
 * Expects:
 * - a filtergroup in options->filtergroup
 * - a controller  in options->controller
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 * @package     Tinebase
 * @subpackage  Filter
 *
 * TODO make filtergroup optional (can be fetched via controller or model)
 */
class Tinebase_Model_Filter_ForeignId extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            $this->_controller = call_user_func($this->_options['controller'] . '::getInstance');
        }
        
        return $this->_controller;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['controller']) || ! isset($_options['filtergroup'])) {
            throw new Tinebase_Exception_InvalidArgument('a controller and a filtergroup must be specified in the options');
        }

        parent::_setOptions($_options);
    }

    /**
     * get foreign filter group
     *
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _setFilterGroup()
    {
        if ($this->_doJoin) {
            $this->_options['subTablename'] = uniqid();
        }

        return parent::_setFilterGroup();
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        /*if ($this->_doJoin && $this->_filterGroup) {
        // this also needs to implement NOT logic
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
            $joinBackend = $this->_getController()->getBackend();
            /** @var Tinebase_ModelConfiguration $mc *
            $mc = $this->_getController()->getModel()::getConfiguration();
            $db = $_backend->getAdapter();
            $_select->join(
                [$this->_options['subTablename'] => $joinBackend->getTablePrefix() . $joinBackend->getTableName()],
                $this->_getQuotedFieldName($_backend) . ' = ' .
                    $db->quoteIdentifier($this->_options['subTablename'] . '.' . ($mc ? $mc->getIdProperty() : 'id')),
                []
            );
            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($groupSelect, $this->_filterGroup, $joinBackend);
            $groupSelect->appendWhere();
            return;
        }*/

        if (! is_array($this->_foreignIds) && null !== $this->_filterGroup) {
            $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
        }

        if (strpos($this->_operator, 'not') === 0) {
            if ($this->_valueIsNull) {
                $_select->where($this->_getQuotedFieldName($_backend) . ' IS NOT NULL');
            } elseif (!empty($this->_foreignIds)) {
                $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
                $valueIdentifier = $this->_getQuotedFieldName($_backend);
                $groupSelect->orWhere($valueIdentifier . ' IS NULL');
                $groupSelect->orWhere($valueIdentifier . ' NOT IN (?)', $this->_foreignIds);
                $groupSelect->appendWhere(Zend_Db_Select::SQL_OR);
            }
        } else {
            if (!$this->_valueIsNull && empty($this->_foreignIds)) {
                $_select->where('1 = 0');
            } else {
                if (empty($this->_foreignIds)) {
                    $_select->where($this->_getQuotedFieldName($_backend) . ' IS NULL');
                } else {
                    $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', $this->_foreignIds);
                }
            }
        }
    }
    
    /**
     * set required grants
     * 
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_filterGroup->setRequiredGrants($_grants);
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    protected function _getGenericFilterInformation()
    {
        list($appName, , $filterName) = explode('_', static::class);
        
        $result = array(
            'linkType'      => 'foreignId',
            'appName'       => $appName,
            'filterName'    => $filterName,
        );
        
        if (isset($this->_options['modelName'])) {
            list(,, $modelName) = explode('_', $this->_options['modelName']);
            $result['modelName'] = $modelName;
        }
        
        return $result;
    }
}
