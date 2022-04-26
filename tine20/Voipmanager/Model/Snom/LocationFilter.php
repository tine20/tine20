<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Location Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Snom_LocationFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Voipmanager_Model_Snom_Location::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('firmware_interval', 'update_policy', 'admin_mode', 'ntp_server', 'http_user', 'description')
            )
        ),
        'name'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'firmware_interval' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'update_policy'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'admin_mode'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'ntp_server'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'http_user'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'       => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
