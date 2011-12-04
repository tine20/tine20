<?php
/**
 * Tine 2.0
 * 
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * content state filter class
 * @package     ActiveSync
 */
class ActiveSync_Model_ContentStateFilter extends Tinebase_Model_Filter_FilterGroup
{    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'ActiveSync_Model_ContentStateFilter';
    
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
        'device_id'            => array('filter' => 'Tinebase_Model_Filter_Id'),
        'class'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'collectionid'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'contentid'            => array('filter' => 'Tinebase_Model_Filter_Id'),
    	'creation_time'        => array('filter' => 'Tinebase_Model_Filter_DateTime'),
    	'is_deleted'           => array('filter' => 'Tinebase_Model_Filter_Bool'),
    );    
}
