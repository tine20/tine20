<?php
/**
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:ExampleRecordFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * ExampleRecord filter Class
 * @package     ExampleApplication
 */
class ExampleApplication_Model_ExampleRecordFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'ExampleApplication';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'example_application_record.id')),
        // @todo add filters
        /*
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('number', 'title'))),
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'showClosed'     => array('custom' => true),
        'isBookable'     => array('custom' => true),
        */
    );
}
