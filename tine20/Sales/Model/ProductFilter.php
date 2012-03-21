<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Product filter Class
 * 
 * @package     Sales
 * @subpackage  Filter
 */
class Sales_Model_ProductFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Sales_Model_ProductFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Sales';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Sales_Model_Product';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Sales_Model_Product')),
        'query'                 => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('description', 'name', 'manufacturer', 'category'))
        ),
        'description'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'created_by'            => array('filter' => 'Tinebase_Model_Filter_User'),
    );
}
