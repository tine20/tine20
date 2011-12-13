<?php
/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ExampleRecord filter Class
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 */
class ExampleApplication_Model_ExampleRecordFilter extends Tinebase_Model_Filter_FilterGroup 
{
	/**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'ExampleApplication_Model_ExampleRecordFilter';
	
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'ExampleApplication';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'ExampleApplication_Model_ExampleRecord';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', /*'...'*/))),
        'container_id'   => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'ExampleApplication')),
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
         'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'example_application_record.id',
            'applicationName' => 'ExampleApplication',
        )),
        // modlog filters
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
    
        // @todo add filters
        /*
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        */
    );
}
