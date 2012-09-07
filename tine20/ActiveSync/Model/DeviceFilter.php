<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * device filter class
 * 
 * @package     ActiveSync
 * @subpackage  Model
 */
class ActiveSync_Model_DeviceFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'ActiveSync_Model_DeviceFilter';
    
/**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'ActiveSync';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        #'query'                => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('n_family', 'n_given', 'org_name', 'email', 'adr_one_locality',))),
        'deviceid'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'owner_id'             => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
