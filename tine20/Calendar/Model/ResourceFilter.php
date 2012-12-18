<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar Resource Filter
 * 
 * @package Calendar
 */
class Calendar_Model_ResourceFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Calendar_Model_ResourceFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                 => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', 'email'))),
        'container_id'          => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Calendar')),
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id'  ),
        'name'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'email'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'is_location'           => array('filter' => 'Tinebase_Model_Filter_Bool'),
    );
}
