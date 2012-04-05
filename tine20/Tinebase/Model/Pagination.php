<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Pagination Class
 * @package Tinebase
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
        'sort'                 => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
        'dir'                  => array('presence'      => 'required',
                                        'allowEmpty'    => false,
                                        array('InArray', array('ASC', 'DESC')),
                                        'default'       => 'ASC'        )
    );
    
    /**
     * Appends pagination statements to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendPaginationSql($_select)
    {
        $this->appendLimit($_select);
        $this->appendSort($_select);
    }
    
    /**
     * Appends limit statement to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendLimit($_select)
    {
        if (! empty($this->limit)) {
            $start = ($this->start >= 0) ? $this->start : 0;
            $_select->limit($this->limit, $start);
        }
    }
    
    /**
     * Appends sort statement to a given select object
     * 
     * @param  Zend_Db_Select
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
        if (is_array($this->sort)) {
            $order = array();
            foreach ($this->sort as $sort) {
                $order[] = $sort . ' ' . $this->dir;
            }
        } else {
            $order = array($this->sort . ' ' . $this->dir);
        }
        
        return $order;
    }
}
