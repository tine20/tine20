<?php
/**
 * Tine 2.0
 * 
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Account filter Class
 * @package     Sipgate
 */
class Sipgate_Model_AccountFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Sipgate_Model_AccountFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Sipgate';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Sipgate_Model_Account';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'          => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('controller' => 'Sipgate_Controller_Account', 'modelName' => 'Sipgate_Model_Account')),
        'description' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'type'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'account_type'        => array('filter' => 'Tinebase_Model_Filter_Text'),#
        'mobile_number'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'created_by'  => array('filter' => 'Tinebase_Model_Filter_User')
    );
}
