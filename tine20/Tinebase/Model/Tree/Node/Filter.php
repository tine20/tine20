<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo 0007376: Tinebase_FileSystem / Node model refactoring: move all container related functionality to Filemanager
 */

/**
 * tree node filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter
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
        'object_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
    // tree_fileobjects table
        'last_modified_time'   => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'deleted_time'         => array(
            'filter' => 'Tinebase_Model_Filter_DateTime',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'creation_time'        => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'last_modified_by'     => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects'
        )),
        'created_by'           => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'type'                 => array(
            'filter' => 'Tinebase_Model_Filter_Text', 
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'contenttype'          => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'description'          => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
    // tree_filerevisions table
        'size'                 => array(
            'filter' => 'Tinebase_Model_Filter_Int',
            'options' => array('tablename' => 'tree_filerevisions')
        ),
    // recursive search
        'recursive' => array(
            'filter' => 'Tinebase_Model_Filter_Bool'
        )
    );
}
