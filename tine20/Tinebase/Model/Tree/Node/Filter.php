<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * tree node filter class
 * 
 * @package     Tinebase
 */
class Tinebase_Model_Tree_Node_Filter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_Tree_Node_Filter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('name'))
        ),
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'path'                 => array('filter' => 'Tinebase_Model_Tree_Node_PathFilter'),
        'parent_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'type'                 => array(
            'filter' => 'Tinebase_Model_Filter_Text', 
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'contenttype'          => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'description'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'size'                 => array(
            'filter' => 'Tinebase_Model_Filter_Int',
            'options' => array('tablename' => 'tree_filerevisions')
        ),
        'object_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
    );    
}
