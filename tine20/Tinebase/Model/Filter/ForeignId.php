<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * foreign id filter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * Expects:
 * - a filtergroup in options->filtergroup
 * - a controller  in options->controller
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 */
class Tinebase_Model_Filter_ForeignId extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_Filter_ForeignId';
    
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
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('controller', $_options) || ! array_key_exists('filtergroup', $_options)) {
            throw new Tinebase_Exception_InvalidArgument('a controller and a filtergroup must be specified in the options');
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
        $db = Tinebase_Core::getDb();
        
        if (! is_array($this->_foreignIds)) {
            $this->_foreignIds = $this->_getController()->search($this->_filterGroup, new Tinebase_Model_Pagination(), FALSE, TRUE);
        }
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_foreignIds) ? array('') : $this->_foreignIds);
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
        list($appName, $i, $filterName) = explode('_', $this->_className);
        
        $result = array(
            'linkType'      => 'foreignId',
            'appName'       => $appName,
            'filterName'    => $filterName,
        );
        
        if (isset($this->_options['modelName'])) {
            list($appName, $i, $modelName) = explode('_', $this->_options['modelName']);
            $result['modelName'] = $modelName;
        }
        
        return $result;
    }
}
