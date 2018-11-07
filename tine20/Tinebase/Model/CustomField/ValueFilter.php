<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * CustomFieldValue filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_CustomField_ValueFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'record_id'         => array('filter' => 'Tinebase_Model_Filter_Id'),
        'customfield_id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
        'value'             => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
