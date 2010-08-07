<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 *  department filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_DepartmentFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Department';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_DepartmentFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                => array('filter' => 'Tinebase_Model_Filter_Id'),
        'name'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'       => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
