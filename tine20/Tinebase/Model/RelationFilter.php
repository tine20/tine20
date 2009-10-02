<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Relation
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Relation Filter Class
 * 
 * @package     Tinebase
 * @subpackage  Relation
 * 
 */
class Tinebase_Model_RelationFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Relation';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_RelationFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                     => array('filter' => 'Tinebase_Model_Filter_Id'),
        'own_model'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'own_backend'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'own_id'                 => array('filter' => 'Tinebase_Model_Filter_Id'),
        'own_degree'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'related_model'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'related_backend'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'related_id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'type'                   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'remark'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'created_by'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'creation_time'          => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'last_modified_by'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'last_modified_time'     => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'is_deleted'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'deleted_time'           => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'deleted_by'             => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}