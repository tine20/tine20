<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use new filter syntax for Voipmanager_Model_Snom_PhoneFilter
 */

/**
 * Call Filter Class
 * @package Phone
 */
class Phone_Model_CallFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Phone';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Phone_Model_Call';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'       => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('source', 'destination'))),
        'phone_id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
    );

    /**
     * is acl filter resolved?
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * appends custom filters to a given select object
     * - add user phone ids to filter
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        // ensure acl policies
        $this->_appendAclSqlFilter($_select);
    }
    
    /**
     * check user phones (add user phone ids to filter
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendAclSqlFilter($_select) {
        
        if (! $this->_isResolved) {
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), true));    
        
            $phoneIdFilter = $this->_findFilter('phone_id');
            
            // set user phone ids as filter
            $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
                array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
            ));        
            $userPhoneIds = Phone_Controller_MyPhone::getInstance()->search($filter)->getArrayOfIds();
            
            if ($phoneIdFilter === NULL) {
                $phoneIdFilter = $this->createFilter('phone_id', 'in', $userPhoneIds);
                $this->addFilter($phoneIdFilter);

            } else {
                $phoneIdFilter->setValue(array_intersect((array) $phoneIdFilter->getValue(), $userPhoneIds));
            }
            
            $this->_isResolved = TRUE;
        }
    }
}
