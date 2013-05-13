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
    
    protected $_defaultFilter = 'query';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', /*'...'*/))),
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
