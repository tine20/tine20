<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *  import filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_ImportFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Import';
    
    /**
     * @var string class name of this filter group
     */
    protected $_className = 'Tinebase_Model_ImportFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'model'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'sourcetype'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'interval'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'source'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'timestamp'      => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'synctoken'      => array('filter' => 'Tinebase_Model_Filter_Text'),
        'container_id'   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'application_id' => array('filter' => 'Tinebase_Model_Filter_Id')
    );
}
