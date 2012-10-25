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
 * Connection filter Class
 * @package     Sipgate
 */
class Sipgate_Model_ConnectionFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Sipgate_Model_ConnectionFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Sipgate';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Sipgate_Model_Connection';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('local_number', 'remote_number', 'contact_name'))),
        'contact_id'           => array('filter' => 'Addressbook_Model_ContactIdFilter'),
        'line_id'  => array('filter' => 'Tinebase_Model_Filter_ForeignId',
            'options' => array(
                'filtergroup'       => 'Sipgate_Model_LineFilter',
                'controller'        => 'Sipgate_Controller_Line',
           )
        ),
        'entry_id'  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tos'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'id'        => array(
            'filter' => 'Tinebase_Model_Filter_Id', 
            'options' => array(
                'modelName'  => 'Sipgate_Model_Connection',
                'controller' => 'Sipgate_Controller_Connection',
                )
            ),
    );
}
