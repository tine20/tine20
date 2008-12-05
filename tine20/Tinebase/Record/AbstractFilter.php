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
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " setting filters from array with data: " .
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
            //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " append sql for filter '$field' width value '$value'");
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
                $containers = $cc->getContainerByACL($currentAccount, $this->_application, Tinebase_Model_Container::GRANT_READ);
                break;
            case 'personal':
                $containers = $currentAccount->getPersonalContainer($this->_application, $this->owner, Tinebase_Model_Container::GRANT_READ);
                break;
            case 'shared':
                $containers = $currentAccount->getSharedContainer($this->_application, Tinebase_Model_Container::GRANT_READ);
                break;
            case 'otherUsers':
                $containers = $currentAccount->getOtherUsersContainer($this->_application, Tinebase_Model_Container::GRANT_READ);
                break;
            case 'internal':
                $containers = array(Tinebase_Container::getInstance()->getInternalContainer($currentAccount, $this->_application));
                break;    
            case 'singleContainer':
                $this->_properties['container'] = array($this->_properties['container']);
                return;
            default:
                throw new Tinebase_Exception_UnexpectedValue('ContainerType not supported.');
        }
        $container = array();
        foreach ($containers as $singleContainer) {
            $container[] = $singleContainer->getId();
        }
        
        $this->_properties['container'] = $container;
    }    
}
