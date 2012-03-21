<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Phone Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Snom_PhoneFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Voipmanager_Model_Snom_PhoneFilter';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Phone';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('macaddress', 'ipaddress', 'description')
            )
        ),
        'account_id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
    
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
        $accountIdFilter = $this->_findFilter('account_id');
        if($accountIdFilter !== NULL) {
            $db = Tinebase_Core::getDb();
            $backend = new Voipmanager_Backend_Snom_Phone();
            $_validPhoneIds = $backend->getValidPhoneIds($accountIdFilter->getValue());
            if(empty($_validPhoneIds)) {
                $_select->where('1=0');
            } else {
                $_select->where($db->quoteInto($db->quoteIdentifier($_backend->getTableName() . '.id') . ' IN (?)', $_validPhoneIds));
            }
            
            // remove filter
            $this->_removeFilter('account_id');
        }
    }
}
