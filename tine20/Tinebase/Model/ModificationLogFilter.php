<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *  Tinebase_Model_ModificationLog filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_ModificationLogFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_ModificationLog';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_ModificationLogFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'application_id'       => array('filter' => 'Tinebase_Model_Filter_Id'),
        'record_id'            => array('filter' => 'Tinebase_Model_Filter_Id'),
        'modification_account' => array('filter' => 'Tinebase_Model_Filter_Id'),
        'modification_time'    => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'record_type'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'modified_attribute'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'seq'                  => array('filter' => 'Tinebase_Model_Filter_Int'),
    );
}
