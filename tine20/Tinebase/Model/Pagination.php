<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Pagination Class
 * @package Tinebase
 *
 * @property string id
 * @property string start
 * @property string limit
 * @property string|array sort
 * @property string|array dir
 * @property string model
 */
class Tinebase_Model_Pagination extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * validators
     * 
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty'    => true,
                                        'Int'                           ),
        'start'                => array('allowEmpty'    => true,
                                        'Int',
                                        'default'       => 0            ),
        'limit'                => array('allowEmpty'    => true,  
                                        'Int',
                                        'default'       => 0            ),
        // can be array for multiple sort rows
        'sort'                 => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
        // can be array of sort dirs for multiple sort rows
        'dir'                  => array('presence'      => 'required',
                                        'allowEmpty'    => false,
                                        'default'       => 'ASC'        ),
        'model'                => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
    );
    
    /**
     * Appends pagination statements to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendPaginationSql($_select)
    {
        // check model for required joins etc.
        $this->appendModelConfig($_select);

        $this->appendLimit($_select);
        $this->appendSort($_select);
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendModelConfig($_select)
    {
        if (empty($this->model) || empty($this->sort) || empty($this->dir)) {
            return;
        }

        /** @var Tinebase_Record_Abstract $model */
        $model = $this->model;
        if (empty($mapping = $model::getSortExternalMapping())) {
            return;
        }
        $joined = array();
        $this->sort = (array)$this->sort;
        foreach ($this->xprops('sort') as &$field) {
            if (!isset($mapping[$field])) {
                continue;
            }
            $mappingDef = $mapping[$field];
            if (isset($mappingDef['fieldCallback'])) {
                $field = call_user_func($mappingDef['fieldCallback'], $field);
            }
            if (isset($joined[$mappingDef['table']])) {
                continue;
            }
            $_select->joinLeft(array($mappingDef['table'] => SQL_TABLE_PREFIX . $mappingDef['table']), $mappingDef['on'],
                array());
            $joined[$mappingDef['table']] = true;
        }
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendLimit($_select)
    {
        if (!empty($this->limit)) {
            $start = ($this->start >= 0) ? $this->start : 0;
            $_select->limit($this->limit, $start);
        }
    }
    
    /**
     * Appends sort statement to a given select object
     * 
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendSort($_select)
    {
        if (!empty($this->sort) && !empty($this->dir)){
            $_select->order($this->_getSortCols());
        }        
    }
    
    /**
     * get columns for select order statement
     * 
     * @return array
     */
    protected function _getSortCols()
    {
        $order = array();
        foreach ((array)$this->sort as $index => $sort) {
            $order[] = $sort . ' ' . (is_array($this->dir)
                        ? $this->dir[$index]
                        : $this->dir
                    );
        }
        return $order;
    }
}
