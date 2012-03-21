<?php
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Project filter Class
 * @package     Projects
 * @subpackage  Model
 */
class Projects_Model_ProjectFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Projects_Model_ProjectFilter';
    
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
        'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('title', 'number'))),
        'container_id'         => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Projects')),
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Projects_Model_Project')),
        'tag'                  => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty'      => 'projects_project.id',
            'applicationName' => 'Projects',
        )),

        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
    
        'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'  => 'Addressbook_Model_Contact',
            'filtergroup'    => 'Addressbook_Model_ContactFilter'
        )),
    
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
    );
}
