<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        use Zend_Filter/Tinebase_Model_Filter for the validators to get a more dynamic structure
 *              in appendFilterSql()
 */

/**
 * Abstract Filter Record Class
 * @package Tasks
 */
abstract class Tinebase_Record_AbstractFilter extends Tinebase_Record_Abstract
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
        'id'                   => array('allowEmpty' => true,  'Int'   ),
        
    // container filter
        'containerType'        => array('allowEmpty' => true           ),
        'owner'                => array('allowEmpty' => true           ),
        'container'            => array('allowEmpty' => true           ),

    // standard query filter
        'query'                => array('allowEmpty' => true           ),
    );
    
    /**
     * name of fields containing date or an array of date information
     *
     * @var array list of date fields
     * 
     * @todo move that to abstrat record?
     */    
    protected $_dateFields = array();
    
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
        'not'      => 'NOT LIKE',
        'within'   => array('>=', '<='),
        'before'   => '<',
        'after'    => '>'
    );
    
    /**
     * fields for the query filter
     *
     * @var array
     */
    protected $_queryFields = array();
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @param boolean $_getOperators legacy: we have filters which don't use the field|operator|value structure
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data, $_getOperators = TRUE)
    {
        //    print_r($_data, true));
        
        if ($_getOperators) {
            // field => value array to fit the record schema
            $data = array();
            //$this->_
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
            
            parent::setFromArray($data);
        } else {
            parent::setFromArray($_data);
        }
    }
    
    /**
     * appends current filters to a given select object
     * 
     * -> if you have special filters, overwrite this function, 
     *    add your filters to the $_select object and
     *    call parent::appendFilterSql($_select)
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendFilterSql($_select)
    {
        $db = Tinebase_Core::getDb();
        
        foreach ($this->_properties as $field => $value)
        {
            if (empty($value)) {
                continue;
            }
            
            $value = str_replace(array('*', '_'), array('%', '\_'), $value);
            
            switch ($field) {
                case 'containerType':
                case 'container':
                case 'owner':
                    // skip container here handling for the moment
                    break;
                case 'query':
                    $queries = explode(' ', $value);
                    foreach ($queries as $query) {
                        $whereParts = array();
                        foreach ($this->_queryFields as $qField) {
                             $whereParts[] = $db->quoteIdentifier($qField) . ' LIKE ?';
                        }                        
                        $whereClause = '';
                        if (!empty($whereParts)) {
                            $whereClause = implode(' OR ', $whereParts);
                        }                        
                        if (!empty($whereClause)) {
                            $_select->where($db->quoteInto($whereClause, '%' . trim($query) . '%'));
                        }
                    }
                    break;
                case 'tag':
                    $operator = $this->_operators[$field];
                    if (strlen($value) == 40) {
                        Tinebase_Tags::appendSqlFilter($_select, $value, $operator);
                    }
                    break;
                default:
                    if (!isset($this->_validators[$field]['special']) || !$this->_validators[$field]['special']) {
                        $operator = $this->_operators[$field];
                        
                        if (in_array($field, $this->_dateFields)) {
                            $value = $this->_getDateValues($operator, $value);
                        }
                        
                        // check if multiple operators/values
                        if (is_array($this->_opSqlMap[$operator]) && is_array($value)) {
                            $where = array();
                            for ($i = 0; $i<sizeof($value); $i++) {
                                $where[] = implode(' ', array(
                                    $db->quoteIdentifier($field),
                                    $this->_opSqlMap[$operator][$i],
                                    $db->quote($value[$i])
                                ));
                            }
                            $_select->where(implode(' AND ', $where)); 
                                                       
                        } else {
                            $value = $operator == 'contains' ? '%' . trim($value) . '%' : trim($value);
                            $where = array(
                                $db->quoteIdentifier($field),
                                $this->_opSqlMap[$operator],
                                $db->quote($value)
                            );
                            $_select->where(implode(' ', $where));
                        }
                    }
            }
        }
    }

    /**
     * gets record related properties
     * 
     * @param string name of property
     * @return mixed value of property
     */
    public function __get($_name)
    {
        switch ($_name) {
            case 'container':
                $this->_resolveContainer();
                break;
            default:
        }
        return parent::__get($_name);
    }
    
    /**
     * set filter value
     *
     * @param string $name
     * @param string|array $value
     * 
     * @todo write test
     */
    public function __set($name, $value)
    {
        if (is_array($value) && isset($value['value']) && array_key_exists($name, $this->_validators)) {            
            if (isset($value['operator'])) {
                $this->_operators[$name] = $value['operator'];
            }
            $this->_options[$name] = array_diff_key($value, array(
                'field'    => NULL, 
                'operator' => NULL,
                'value'    => NULL
            ));
            $value = $value['value'];
        }
                        
        parent::__set($name, $value);
    }
    
    /**
     * Resolves containers from selected nodes
     * 
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    protected function _resolveContainer()
    {
        if (isset($this->_properties['container']) && is_array($this->_properties['container'])) {
            return;
        }
        if (!$this->containerType) {
            $this->containerType = 'all';
            //throw new Tinebase_Exception_UnexpectedValue('You need to set a containerType.');
        }
        if ($this->containerType == 'Personal' && !$this->owner) {
            throw new Tinebase_Exception_UnexpectedValue('You need to set an owner when containerType is "Personal".');
        }
        
        $currentAccount = Tinebase_Core::getUser();        
        $cc = Tinebase_Container::getInstance();
        switch($this->containerType) {
            case 'all':
                $container = $cc->getContainerByACL($currentAccount, $this->_application, Tinebase_Model_Container::GRANT_READ, TRUE);
                break;
            case 'personal':
                $container = $currentAccount->getPersonalContainer($this->_application, $this->owner, Tinebase_Model_Container::GRANT_READ)->getId();
                break;
            case 'shared':
                $container = $currentAccount->getSharedContainer($this->_application, Tinebase_Model_Container::GRANT_READ)->getId();
                break;
            case 'otherUsers':
                $container = $currentAccount->getOtherUsersContainer($this->_application, Tinebase_Model_Container::GRANT_READ)->getId();
                break;
            case 'internal':
                $container = array(Tinebase_Container::getInstance()->getInternalContainer($currentAccount, $this->_application)->getId());
                break;    
            case 'singleContainer':
                $this->_properties['container'] = array($this->_properties['container']);
                return;
            default:
                throw new Tinebase_Exception_UnexpectedValue('ContainerType not supported.');
        }
        
        $this->_properties['container'] = $container;
    }    

    /**
     * calculates the date filter values
     *
     * @param string $_operator
     * @param string $_value
     * @param string $_dateFormat
     * @return array|string date value
     * 
     */
    protected function _getDateValues($_operator, $_value, $_dateFormat = 'yyyy-MM-dd')
    {        
        if ($_operator === 'before' || $_operator === 'after') {
            $value = substr($_value, 0, 10);

        } else {
            $date = new Zend_Date();
            $dayOfWeek = $date->get(Zend_Date::WEEKDAY_DIGIT);
            
            // special values like this week, ...
            switch($_value) {
                case 'weekBeforeLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekLast':    
                    $date->sub(7, Zend_Date::DAY);
                case 'weekThis':
                    $date->sub($dayOfWeek-1, Zend_Date::DAY);
                    $monday = $date->toString($_dateFormat);
                    $date->add(6, Zend_Date::DAY);
                    $sunday = $date->toString($_dateFormat);
                    
                    $value = array(
                        $monday, 
                        $sunday,
                    );
                    break;
                case 'monthLast':
                    $date->sub(1, Zend_Date::MONTH);
                case 'monthThis':
                    $dayOfMonth = $date->get(Zend_Date::DAY_SHORT);
                    $monthDays = $date->get(Zend_Date::MONTH_DAYS);
                    
                    $first = $date->toString('yyyy-MM');
                    $date->add($monthDays-$dayOfMonth, Zend_Date::DAY);
                    $last = $date->toString($_dateFormat);
    
                    $value = array(
                        $first, 
                        $last,
                    );
                    break;
                case 'yearLast':
                    $date->sub(1, Zend_Date::YEAR);
                case 'yearThis':
                    $value = array(
                        $date->toString('yyyy') . '-01-01', 
                        $date->toString('yyyy') . '-12-31',
                    );                
                    break;
                case 'quarterLast':
                    $date->sub(3, Zend_Date::MONTH);
                case 'quarterThis':
                    $month = $date->get(Zend_Date::MONTH);
                    if ($month < 4) {
                        $first = $date->toString('yyyy' . '-01-01');
                        $last = $date->toString('yyyy' . '-03-31');
                    } elseif ($month < 7) {
                        $first = $date->toString('yyyy' . '-04-01');
                        $last = $date->toString('yyyy' . '-06-30');
                    } elseif ($month < 10) {
                        $first = $date->toString('yyyy' . '-07-01');
                        $last = $date->toString('yyyy' . '-09-30');
                    } else {
                        $first = $date->toString('yyyy' . '-10-01');
                        $last = $date->toString('yyyy' . '-12-31');
                    }
                    $value = array(
                        $first, 
                        $last
                    );                
                    break;
                default:
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value unknown: ' . $_value);
                    $value = '';
            }        
        }
        
        return $value;
    }
}
