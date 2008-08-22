<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 *  notes filter class
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Model_NoteFilter extends Tinebase_Record_Abstract
{
    
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        'id'                     => array('allowEmpty' => true,  'Int'   ),
        
        'note_type_id'           => array('allowEmpty' => true),
    
        'query'                  => array('allowEmpty' => true),
            
        'record_id'              => array('allowEmpty' => true),
        'record_model'           => array('allowEmpty' => true),
        'record_backend'         => array('allowEmpty' => true),
    
        'created_by'             => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
    );
    
    /**
     * @var array hold selected operators
     */
    protected $_operators = array();
    
    /**
     * @var array holds additional options
     */
    protected $_options = array();
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'contains' => 'LIKE',
        'equals'   => 'LIKE',
        'greater'  => '>',
        'less'     => '<',
        'not'      => 'NOT LIKE'
    );
        
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @throws Tinebase_Record_Exception_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        $data = array();
        foreach ($_data as $filter) {
            $field = (isset($filter['field']) && isset($filter['value'])) ? $filter['field'] : '';
            if (array_key_exists($field, $this->_validators)) {
                $data[$field] = $filter['value'];
                $this->_operators[$field] = $filter['operator'];
                $this->_options[$field] = array_diff_key($filter, array(
                    'field'    => NULL, 
                    'operator' => NULL,
                    'value'    => NULL
                ));
            }
        }
        
        if ($this->bypassFilters !== true) {
            // $this->validateOperators();
            // $this->validateOptions();
        }
        parent::setFromArray($data);
    }
    
    /**
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     * 
     * @todo add created_by filter (join with user table for that?)
     */
    public function appendFilterSql($_select)
    {
        $db = Zend_Registry::get('dbAdapter');
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_properties, true));
        
        foreach ($this->_properties as $field => $value)
        {
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . $field . ' - ' . $value);
            
            $value = str_replace(array('*', '_'), array('%', '\_'), $value);
            $op = $this->_operators[$field];
            
            switch ($field) {
                case 'created_by':
                    //--
                    break;
                case 'query':
                    $_select->where($db->quoteInto('(note LIKE ?)', '%' . trim($value) . '%'));
                    break;
                default:
                    $value = $op == 'contains' ? '%' . trim($value) . '%' : trim($value);
                    $where = array(
                        $db->quoteIdentifier($field),
                        $this->_opSqlMap[$op],
                        $db->quote($value)
                    );
                    $_select->where(implode(' ', $where));
                    break;
            }
        }
    }
}
