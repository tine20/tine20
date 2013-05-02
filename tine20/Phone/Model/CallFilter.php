<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use new filter syntax for Voipmanager_Model_Snom_PhoneFilter
 */

/**
 * Call Filter Class
 * 
 * @package Phone
 * @subpackage  Model
 */
class Phone_Model_CallFilter extends Tinebase_Model_Filter_FilterGroup
{
    protected $_configuredModel = 'Phone_Model_Call';

    /**
     * is acl filter resolved?
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * Gets the phone id filter for the first record in the given recordset
     * 
     * @param Tinebase_Record_RecordSet $userPhones
     * @return Tinebase_Model_Filter_Abstract
     */
    protected function _getDefaultPhoneFilter($userPhones)
    {
        $record = $userPhones->getFirstRecord();
        if ($record) {
            $filter = $this->createFilter(
                array('field' => 'phone_id', 'operator' => 'AND', 'value' => array(
                    array('field' => ':id', 'operator' => 'equals', 'value' => $record->getId())
                ))
            );
        } else {
            $filter = new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'equals', 'value' => 'notexists'));
        }
        
        $filter->setIsImplicit(TRUE);
        return $filter;
    }
    
    /**
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (! $this->_isResolved) {
            $phoneIdFilter = $this->_findFilter('phone_id');
            
            // set user phone ids as filter
            $filter = new Voipmanager_Model_Snom_PhoneFilter(array(
                array('field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
            ));
            
            $userPhones = Phone_Controller_MyPhone::getInstance()->search($filter);
            $userPhoneIds = $userPhones->getArrayOfIds();

            if ($phoneIdFilter === NULL) {
                $this->addFilter($this->_getDefaultPhoneFilter($userPhones));
            } else {
                $phoneId = $phoneIdFilter->getValue();
                $phoneId = $phoneId[0]['value'];
                if (! in_array($phoneId, $userPhoneIds)) {
                    $this->removeFilter('phone_id');
                    $this->addFilter($this->_getDefaultPhoneFilter($userPhones));
                }
            }
            $this->_isResolved = TRUE;
        }
    }
}
