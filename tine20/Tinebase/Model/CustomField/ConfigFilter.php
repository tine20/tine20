<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * CustomFieldConfig filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_CustomField_ConfigFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_CustomField_ConfigFilter';
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_CustomField_Grant::GRANT_READ
    );
        
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'application_id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
        'name'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'model'             => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
    
    /**
     * is acl filter resolved?
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * check for customfield ACLs
     *
     * @var boolean
     * 
     */
    protected $_customfieldACLChecks = TRUE;
    
    /**
     * set/get checking ACL
     * 
     * @param  boolean optional
     * @return boolean
     */
    public function customfieldACLChecks()
    {
        $currValue = $this->_customfieldACLChecks;
        if (func_num_args() === 1) {
            $paramValue = (bool) func_get_arg(0);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resetting customfieldACLChecks to ' . (int) $paramValue);
            $this->_customfieldACLChecks = $paramValue;
        }
        
        return $currValue;
    }
    
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
        $this->_isResolved = FALSE;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function appendFilterSql($_select, $_backend)
    {
        if ($this->_customfieldACLChecks) {
            // only search for ids for which the user has the required grants
            if (! $this->_isResolved) {
                $result = array();
                foreach ($this->_requiredGrants as $grant) {
                    $result = array_merge($result, Tinebase_CustomField::getInstance()->getCustomfieldConfigIdsByAcl($grant));
                }
                $this->_validCustomfields = array_unique($result);
                $this->_isResolved = TRUE;
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Found ' . print_r($result, TRUE));
            }
            
            $db = Tinebase_Core::getDb();
            
            $field = $db->quoteIdentifier('id');
            $where = $db->quoteInto("$field IN (?)", empty($this->_validCustomfields) ? array('') : $this->_validCustomfields);
            
            $_select->where($where);
        }
    }
}
