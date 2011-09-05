<?php
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Project filter Class
 * @package     Projects
 */
class Projects_Model_ProjectFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Projects';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Projects_Model_Project';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', /*'...'*/))),
        'container_id'   => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Projects')),
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'example_application_record.id')),
        
        // @todo add filters
        /*
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'showClosed'     => array('custom' => true),
        'isBookable'     => array('custom' => true),
        */
    );
}
